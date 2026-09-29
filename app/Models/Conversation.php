<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use App\Enums\ConversationChannelType;
use App\Enums\ConversationPriority;
use App\Enums\ConversationStatus;
use App\Enums\ConversationType;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $dispatchesEvents = [
        'created' => \App\Events\ConversationCreated::class,
    ];

    protected $fillable = [
        'workspace_id', 'channel_type', 'channel_id', 'contact_id', 'visitor_id',
        'assigned_operator_id', 'department_id', 'sla_id', 'type', 'status',
        'priority', 'subject', 'last_message_at', 'closed_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'closed_at' => 'datetime',
        'channel_type' => ConversationChannelType::class,
        'type' => ConversationType::class,
        'status' => ConversationStatus::class,
        'priority' => ConversationPriority::class,
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function assignedOperator(): BelongsTo
    {
        // withTrashed(): a resolved conversation must still show who handled
        // it even if that operator's account was later soft-deleted.
        return $this->belongsTo(User::class, 'assigned_operator_id')->withTrashed();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function sla(): BelongsTo
    {
        return $this->belongsTo(Sla::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function csatRating(): HasOne
    {
        return $this->hasOne(CsatRating::class);
    }

    public function metric(): HasOne
    {
        return $this->hasOne(ConversationMetric::class);
    }

    public function aiMeta(): HasOne
    {
        return $this->hasOne(AiConversationMeta::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
