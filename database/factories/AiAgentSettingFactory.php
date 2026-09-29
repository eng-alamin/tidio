<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiAgentSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'tone' => 'friendly',
            'default_language' => 'en',
            'handoff_rules' => ['on_low_confidence' => true, 'threshold' => 0.6],
            'is_active' => true,
        ];
    }
}
