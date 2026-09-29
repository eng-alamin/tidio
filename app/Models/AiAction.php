<?php

namespace App\Models;

use App\Enums\AiActionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAction extends Model
{
    use HasFactory;

    protected $fillable = ['workspace_id', 'name', 'type', 'endpoint_url', 'config', 'is_active'];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'type' => AiActionType::class,
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
