<?php

namespace App\Services;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * One place for everything about message attachments: validating, storing, describing and
 * serving them. Files are stored on a private disk under
 * `attachments/{workspace_id}/{conversation_id}/{uuid}.{ext}` and referenced from
 * `messages.attachments` (JSON) by a descriptor:
 *
 *   { id, name, ext, mime, size, kind: "image"|"file", path }
 *
 * `path` is internal and must never be sent to a browser — use forClient() / publicShape().
 * The Content-Type of a download comes from our own extension map, never from the client.
 */
class AttachmentService
{
    /** Extension => the content types a genuine file of that kind may be sniffed as. */
    private const CONTENT_TYPES = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'pdf' => ['application/pdf'],
        'txt' => ['text/plain'],
        'csv' => ['text/plain', 'text/csv', 'application/csv'],
    ];

    /** What we tell the browser when serving. Anything not listed is a generic download. */
    private const SERVE_AS = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
        'txt' => 'text/plain; charset=UTF-8',
        'csv' => 'text/csv; charset=UTF-8',
    ];

    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /** Office files and zips are containers that file-sniffers report in several different ways. */
    private const CONTAINER_TYPES = [
        'application/zip',
        'application/x-zip-compressed',
        'application/octet-stream',
        'application/msword',
        'application/cdfv2',
        'application/x-ole-storage',
    ];

    public function enabled(): bool
    {
        return (bool) config('widget.attachments.enabled', true);
    }

    public function maxFiles(): int
    {
        return max(1, (int) config('widget.attachments.max_files', 3));
    }

    public function maxKb(): int
    {
        return max(1, (int) config('widget.attachments.max_kb', 5120));
    }

    /** @return array<int, string> */
    public function extensions(): array
    {
        return array_values(array_map('strtolower', (array) config('widget.attachments.extensions', [])));
    }

    /** Comma separated ".jpg,.png,..." for an <input accept="">. */
    public function acceptAttribute(): string
    {
        return implode(',', array_map(fn (string $e) => '.'.$e, $this->extensions()));
    }

    /**
     * Validation rules for an uploaded-files field, e.g. rules('files') or rules('uploads').
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(string $field = 'files'): array
    {
        if (! $this->enabled()) {
            return [$field => ['prohibited']];
        }

        return [
            $field => ['sometimes', 'array', 'max:'.$this->maxFiles()],
            $field.'.*' => [
                'file',
                'max:'.$this->maxKb(),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $value instanceof UploadedFile || ! $this->contentAllowed($value)) {
                        $fail('This file type is not allowed.');
                    }
                },
            ],
        ];
    }

    /** Extension is on the allow-list AND the file's real content matches it. */
    public function contentAllowed(UploadedFile $file): bool
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if (! in_array($ext, $this->extensions(), true)) {
            return false;
        }

        $mime = strtolower((string) $file->getMimeType()); // sniffed from the bytes, not the client header

        if (isset(self::CONTENT_TYPES[$ext])) {
            return in_array($mime, self::CONTENT_TYPES[$ext], true);
        }

        return in_array($mime, self::CONTAINER_TYPES, true)
            || str_starts_with($mime, 'application/vnd.openxmlformats-officedocument.')
            || str_starts_with($mime, 'application/vnd.ms-');
    }

    /**
     * Stores already-validated files and returns their descriptors. All-or-nothing: if one file
     * can't be written, the ones already written are removed again before the error is rethrown.
     *
     * @param  array<int, UploadedFile>  $files
     * @return array<int, array<string, mixed>>
     */
    public function store(array $files, int $workspaceId, int $conversationId): array
    {
        $disk = Storage::disk($this->disk());
        $directory = "attachments/{$workspaceId}/{$conversationId}";
        $stored = [];

        try {
            foreach ($files as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $ext = strtolower($file->getClientOriginalExtension());

                if (! in_array($ext, $this->extensions(), true)) {
                    throw new InvalidArgumentException('Attachment type is not allowed.');
                }

                $id = (string) Str::uuid();
                $path = $disk->putFileAs($directory, $file, $id.'.'.$ext);

                if ($path === false) {
                    throw new RuntimeException('The attachment could not be stored.');
                }

                $stored[] = [
                    'id' => $id,
                    'name' => $this->safeName($file->getClientOriginalName(), $ext),
                    'ext' => $ext,
                    'mime' => self::SERVE_AS[$ext] ?? 'application/octet-stream',
                    'size' => (int) $file->getSize(),
                    'kind' => in_array($ext, self::IMAGE_EXTENSIONS, true) ? 'image' : 'file',
                    'path' => $path,
                ];
            }
        } catch (Throwable $e) {
            $this->discard($stored);

            throw $e;
        }

        return $stored;
    }

    /**
     * Deletes stored files. Only paths inside `attachments/` are ever touched.
     *
     * @param  array<int, array<string, mixed>>|null  $descriptors
     */
    public function discard(?array $descriptors): void
    {
        $paths = collect($descriptors ?? [])
            ->pluck('path')
            ->filter(fn ($p) => is_string($p) && str_starts_with($p, 'attachments/') && ! str_contains($p, '..'))
            ->values()
            ->all();

        if ($paths !== []) {
            try {
                Storage::disk($this->disk())->delete($paths);
            } catch (Throwable $e) {
                report($e); // a leftover file must never break deleting a message
            }
        }
    }

    /**
     * The descriptor with this id inside a message's attachments, or null.
     *
     * @param  array<int, array<string, mixed>>|null  $attachments
     * @return array<string, mixed>|null
     */
    public function find(?array $attachments, string $id): ?array
    {
        foreach ($attachments ?? [] as $descriptor) {
            if (is_array($descriptor) && ($descriptor['id'] ?? null) === $id && isset($descriptor['path'])) {
                return $descriptor;
            }
        }

        return null;
    }

    /** Streams one attachment: images inline (for thumbnails), everything else as a download. */
    public function response(array $descriptor): Response
    {
        $disk = Storage::disk($this->disk());
        $path = (string) ($descriptor['path'] ?? '');

        abort_unless(str_starts_with($path, 'attachments/') && ! str_contains($path, '..') && $disk->exists($path), 404);

        return $disk->response($path, (string) ($descriptor['name'] ?? 'file'), [
            'Content-Type' => (string) ($descriptor['mime'] ?? 'application/octet-stream'),
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'private, max-age=3600',
        ], ($descriptor['kind'] ?? 'file') === 'image' ? 'inline' : 'attachment');
    }

    /**
     * Browser-safe list (no storage path) with a download URL from the caller.
     *
     * @param  array<int, array<string, mixed>>|null  $attachments
     * @param  Closure(array<string, mixed>): string  $urlFor
     * @return array<int, array<string, mixed>>
     */
    public function forClient(?array $attachments, Closure $urlFor): array
    {
        return collect($attachments ?? [])
            ->filter(fn ($d) => is_array($d) && isset($d['id'], $d['path']))
            ->map(fn (array $d) => $this->publicShape($d) + ['url' => $urlFor($d)])
            ->values()
            ->all();
    }

    /**
     * Describes an attachment without any internal detail — safe for API responses and views.
     *
     * @param  array<string, mixed>  $descriptor
     * @return array{id: string, name: string, kind: string, size: int, mime: string}
     */
    public function publicShape(array $descriptor): array
    {
        return [
            'id' => (string) ($descriptor['id'] ?? ''),
            'name' => (string) ($descriptor['name'] ?? 'file'),
            'kind' => ($descriptor['kind'] ?? 'file') === 'image' ? 'image' : 'file',
            'size' => (int) ($descriptor['size'] ?? 0),
            'mime' => (string) ($descriptor['mime'] ?? 'application/octet-stream'),
        ];
    }

    public function disk(): string
    {
        return (string) config('widget.attachments.disk', 'local');
    }

    /** Display name only (never used as a path): no folders, control characters or reserved symbols. */
    private function safeName(string $original, string $ext): string
    {
        $base = pathinfo($original, PATHINFO_FILENAME);
        $base = preg_replace('/[\x00-\x1F\x7F\/\\\\:*?"<>|]+/u', '', $base) ?? '';
        $base = trim(preg_replace('/\s+/u', ' ', $base) ?? '');
        $base = mb_substr($base !== '' ? $base : 'file', 0, 80);

        return $base.'.'.$ext;
    }
}
