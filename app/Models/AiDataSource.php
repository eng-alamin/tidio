<?php

namespace App\Models;

use App\Enums\AiDataSourceStatus;
use App\Enums\AiDataSourceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AiDataSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id', 'type', 'source', 'title', 'content', 'file_path', 'status', 'error', 'content_hash',
        'pages_count', 'last_synced_at', 'hits_count', 'success_count',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
        'type' => AiDataSourceType::class,
        'status' => AiDataSourceStatus::class,
        'hits_count' => 'integer',
        'success_count' => 'integer',
        'pages_count' => 'integer',
    ];

    /** Columns for lists: everything except `content`, which can be hundreds of KB per row. */
    public const LIST_COLUMNS = [
        'id', 'workspace_id', 'type', 'source', 'title', 'file_path', 'status', 'error', 'pages_count',
        'last_synced_at', 'hits_count', 'success_count', 'created_at', 'updated_at',
    ];

    protected static function booted(): void
    {
        // An uploaded PDF lives on the private disk — remove it together with its data source.
        static::deleted(function (AiDataSource $source) {
            if ($source->file_path) {
                Storage::disk('local')->delete($source->file_path);
            }
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function successRate(): ?float
    {
        return $this->hits_count > 0
            ? round(($this->success_count / $this->hits_count) * 100)
            : null;
    }
}