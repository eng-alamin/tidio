<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Webhook extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['workspace_id', 'url', 'events', 'secret', 'is_active'];

    protected $casts = ['events' => 'array', 'is_active' => 'boolean'];

    protected $hidden = ['secret'];

    protected static function booted(): void
    {
        static::creating(function (Webhook $webhook) {
            $webhook->events ??= ['conversation.created', 'message.received'];
            $webhook->secret ??= Str::random(40);
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}