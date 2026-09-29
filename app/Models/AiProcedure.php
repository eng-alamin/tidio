<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiProcedure extends Model
{
    use HasFactory;

    protected $fillable = ['workspace_id', 'title', 'instructions', 'trigger_condition', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
