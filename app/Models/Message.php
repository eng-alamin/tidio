<?php

namespace App\Models;

use App\Enums\MessageSenderType;
use App\Services\AttachmentService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Message extends Model
{
    use HasFactory;

    /**
     * MessageSent is a plain domain event (metrics listeners, real-time signal). It is deliberately
     * NOT broadcast with the message inside it: the old version pushed the whole model — including
     * private notes — to a public channel.
     */
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

    protected static function booted(): void
    {
        // Deleting a message through Eloquent also removes its files from storage.
        static::deleted(function (Message $message): void {
            if (! empty($message->attachments)) {
                app(AttachmentService::class)->discard($message->attachments);
            }
        });
    }

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

    /** True when the message carries at least one stored file. */
    public function hasAttachments(): bool
    {
        return ! empty($this->attachments);
    }

    /** One-line text for lists: the body, or the file names for an attachment-only message. */
    public function previewText(int $limit = 0): string
    {
        $text = trim((string) $this->body);

        if ($text === '' && $this->hasAttachments()) {
            $text = '📎 '.collect($this->attachments)->pluck('name')->filter()->implode(', ');
        }

        return $limit > 0 ? Str::limit($text, $limit) : $text;
    }
}
