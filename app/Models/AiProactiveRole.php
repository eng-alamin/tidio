<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiProactiveRole extends Model
{
    use HasFactory;

    protected $fillable = ['workspace_id', 'name', 'goal', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
