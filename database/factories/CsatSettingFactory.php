<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class CsatSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'is_enabled' => true,
            'trigger' => 'after_resolution',
            'survey_question' => 'How would you rate this conversation?',
            'follow_up_question' => 'Anything we could improve?',
        ];
    }
}
