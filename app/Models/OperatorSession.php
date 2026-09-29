<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per online session an operator has in a workspace. `ended_at` is
 * null while the operator is currently online. Powers the "Online hours"
 * analytics tab. Nothing currently opens/closes these rows automatically —
 * wire this up wherever operator presence is tracked (login, a heartbeat
 * ping, or a logout/idle-timeout event) once that exists.
 */
class OperatorSession extends Model
{
    use HasFactory;

    protected $fillable = ['workspace_id', 'user_id', 'started_at', 'ended_at'];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function durationInSeconds(): int
    {
        return $this->started_at->diffInSeconds($this->ended_at ?? now());
    }
}
