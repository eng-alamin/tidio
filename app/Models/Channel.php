<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\ChannelType;

class Channel extends Model
{
    use HasFactory;

    protected $fillable = ['workspace_id', 'type', 'credentials', 'status', 'connected_at'];

    protected $casts = ['connected_at' => 'datetime', 'type' => ChannelType::class, 'credentials' => 'encrypted:array',];

    protected $hidden = ['credentials'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
