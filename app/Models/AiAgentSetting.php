<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentSetting extends Model
{
    use HasFactory;

    protected $fillable = ['workspace_id', 'tone', 'default_language', 'handoff_rules', 'is_active'];

    protected $casts = ['handoff_rules' => 'array', 'is_active' => 'boolean'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
