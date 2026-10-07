<?php

namespace App\Models;

use App\Enums\PublishStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'blog_category_id', 'author_id', 'title', 'slug', 'excerpt', 'body',
        'cover_image', 'status', 'seo_meta', 'published_at',
    ];

    protected $casts = [
        'seo_meta' => 'array',
        'published_at' => 'datetime',
        'status' => PublishStatus::class,
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Visible on the public site: published, and not scheduled for later. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PublishStatus::Published)
            ->where('published_at', '<=', now());
    }

    public function getUrlAttribute(): string
    {
        return route('site.blog.show', $this->slug);
    }

    /** Linked user's name, else seo_meta['author'] (staff writers without an account), else a generic byline. */
    public function getAuthorNameAttribute(): string
    {
        return $this->author?->name ?? ($this->seo_meta['author'] ?? 'The Loop team');
    }

    public function getAuthorInitialsAttribute(): string
    {
        return Str::of($this->author_name)->explode(' ')->filter()->take(2)
            ->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))->implode('');
    }

    /** Bootstrap-icon class shown on cards without a cover image. */
    public function getIconAttribute(): string
    {
        return $this->seo_meta['icon'] ?? 'bi-pencil-square';
    }

    public function getReadMinutesAttribute(): int
    {
        $words = count(preg_split('/\s+/u', trim(strip_tags($this->body)), -1, PREG_SPLIT_NO_EMPTY));

        return max(1, (int) ceil($words / 180));
    }

    /**
     * Body is trusted staff HTML when it starts with a tag (the imported articles).
     * Anything else (plain text, the factory's lorem) is escaped and wrapped in paragraphs.
     */
    public function getBodyHtmlAttribute(): string
    {
        $body = trim((string) $this->body);

        if (str_starts_with($body, '<')) {
            return $body;
        }

        return collect(preg_split('/\R{2,}/', $body, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($paragraph) => '<p>'.nl2br(e(trim($paragraph))).'</p>')
            ->implode("\n");
    }

    /** "On this page" entries: every <h2 id="..."> in the body. @return array<int, array{id: string, title: string}> */
    public function getTocAttribute(): array
    {
        preg_match_all('/<h2[^>]*\sid="([^"]+)"[^>]*>(.*?)<\/h2>/si', $this->body_html, $matches, PREG_SET_ORDER);

        return array_map(fn ($m) => ['id' => $m[1], 'title' => trim(strip_tags($m[2]))], $matches);
    }
}
