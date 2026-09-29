<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConversationMeta extends Model
{
    use HasFactory;

    protected $table = 'ai_conversation_meta';

    protected $fillable = ['conversation_id', 'resolved_by_ai', 'confidence_score', 'handed_off_at'];

    protected $casts = [
        'resolved_by_ai' => 'boolean',
        'confidence_score' => 'decimal:2',
        'handed_off_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
