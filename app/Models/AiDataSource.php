<?php

namespace App\Models;

use App\Enums\AiDataSourceStatus;
use App\Enums\AiDataSourceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiDataSource extends Model
{
    use HasFactory;

    protected $fillable = ['workspace_id', 'type', 'source', 'status', 'last_synced_at', 'hits_count', 'success_count'];

    protected $casts = [
        'last_synced_at' => 'datetime',
        'type' => AiDataSourceType::class,
        'status' => AiDataSourceStatus::class,
        'hits_count' => 'integer',
        'success_count' => 'integer',
    ];

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
