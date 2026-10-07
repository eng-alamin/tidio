<?php

namespace App\Services\Knowledge;

use App\Enums\AiDataSourceStatus;
use App\Enums\AiDataSourceType;
use App\Models\AiDataSource;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Reads one data source (a website address, an uploaded PDF or a PDF link) and stores its text on
 * the row, so Lyro can answer from it. The status moves Pending → Syncing → Synced | Failed, and
 * a failure always carries a message the workspace owner can act on.
 */
class DataSourceSyncer
{
    /** Below this many characters a source is treated as "nothing readable". */
    private const MIN_CHARS = 40;

    public function __construct(
        private readonly WebsiteCrawler $crawler,
        private readonly SafeFetcher $fetcher,
        private readonly PdfTextExtractor $pdf,
    ) {
    }

    public function sync(AiDataSource $source): void
    {
        if ($source->type === AiDataSourceType::Faq) {
            return; // Q&A text is already the knowledge — nothing to read
        }

        $source->forceFill(['status' => AiDataSourceStatus::Syncing, 'error' => null])->save();

        try {
            $result = $this->read($source);

            $content = trim($result['text']);

            if (mb_strlen($content) < self::MIN_CHARS) {
                throw new CrawlException('No readable text was found (a page that needs JavaScript to show its content cannot be read).');
            }

            $cap = (int) config('lyro.crawl.max_chars', 300000);
            $content = mb_strlen($content) > $cap ? mb_substr($content, 0, $cap) : $content;

            $source->forceFill([
                'title' => mb_substr($result['title'], 0, 255) ?: null,
                'content' => $content,
                'content_hash' => hash('sha256', $content),
                'pages_count' => $result['pages'],
                'status' => AiDataSourceStatus::Synced,
                'error' => null,
                'last_synced_at' => now(),
            ])->save();
        } catch (CrawlException $e) {
            $this->fail($source, $e->getMessage());
        } catch (Throwable $e) {
            report($e);
            $this->fail($source, 'Something went wrong while reading this source. Please try again.');
        }
    }

    /** @return array{title: string, text: string, pages: int} */
    private function read(AiDataSource $source): array
    {
        if ($source->type === AiDataSourceType::Pdf) {
            return $this->readPdf($source);
        }

        return $this->crawler->crawl($source->source);
    }

    /** @return array{title: string, text: string, pages: int} */
    private function readPdf(AiDataSource $source): array
    {
        if ($source->file_path) {
            $disk = Storage::disk('local');

            if (! $disk->exists($source->file_path)) {
                throw new CrawlException('The uploaded file is no longer on the server. Please upload it again.');
            }

            $bytes = (string) $disk->get($source->file_path);
        } elseif (preg_match('#^https?://#i', $source->source)) {
            $page = $this->fetcher->get($source->source);

            if ($page['type'] !== 'pdf') {
                throw new CrawlException('That address is not a PDF file.');
            }

            $bytes = $page['body'];
        } else {
            throw new CrawlException('Upload the PDF file again, or enter the web address of the PDF.');
        }

        return $this->pdf->extract($bytes);
    }

    private function fail(AiDataSource $source, string $message): void
    {
        $source->forceFill([
            'status' => AiDataSourceStatus::Failed,
            'error' => mb_substr($message, 0, 500),
        ])->save();
    }
}
