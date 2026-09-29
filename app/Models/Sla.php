<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sla extends Model
{
    use HasFactory;

    protected $table = 'slas';

    protected $fillable = [
        'workspace_id', 'name', 'first_response_minutes', 'resolution_minutes', 'conditions', 'is_default',
    ];

    protected $casts = ['conditions' => 'array', 'is_default' => 'boolean'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
