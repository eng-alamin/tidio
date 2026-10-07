<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id', 'agent_name', 'tone', 'guidance_instructions', 'default_language',
        'handoff_rules', 'channel_rules', 'audience_answer_for', 'audience_exclude_tag', 'audience_countries',
        'copilot_suggest_replies', 'copilot_summarize', 'is_active',
        'playground_tested_at', 'went_live_at',
    ];

    protected $casts = [
        'handoff_rules' => 'array',
        'channel_rules' => 'array',
        'audience_countries' => 'array',
        'copilot_suggest_replies' => 'boolean',
        'copilot_summarize' => 'boolean',
        'is_active' => 'boolean',
        'playground_tested_at' => 'datetime',
        'went_live_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
