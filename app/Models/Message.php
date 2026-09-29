<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\MessageSenderType;

class Message extends Model
{
    use HasFactory;

    protected $dispatchesEvents = [
        'created' => \App\Events\MessageSent::class,
    ];

    protected $fillable = [
        'conversation_id', 'sender_type', 'sender_id', 'body',
        'attachments', 'is_private_note', 'read_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_private_note' => 'boolean',
        'read_at' => 'datetime',
        'sender_type' => MessageSenderType::class,
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(Mention::class);
    }

    /**
     * Resolves to the User or Visitor model depending on sender_type,
     * since sender_id is a polymorphic-style plain FK (not morphs()).
     */
    public function sender(): BelongsTo
    {
        return $this->sender_type === MessageSenderType::Visitor
            ? $this->belongsTo(Visitor::class, 'sender_id')
            : $this->belongsTo(User::class, 'sender_id')->withTrashed();
    }
}
