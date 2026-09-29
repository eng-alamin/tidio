<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CsatRating extends Model
{
    use HasFactory;

    protected $fillable = ['conversation_id', 'rating', 'comment', 'rated_at'];

    protected $casts = ['rated_at' => 'datetime'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
