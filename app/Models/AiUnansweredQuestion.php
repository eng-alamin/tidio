<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUnansweredQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['workspace_id', 'question', 'asked_count', 'resolved_at'];

    protected $casts = ['resolved_at' => 'datetime'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
