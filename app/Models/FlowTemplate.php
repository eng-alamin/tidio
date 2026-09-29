<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['workspace_id', 'name', 'category', 'description', 'canvas_json'];

    protected $casts = ['canvas_json' => 'array'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
