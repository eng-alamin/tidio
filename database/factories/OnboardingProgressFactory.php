<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class OnboardingProgressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'step_key' => fake()->randomElement(['install_widget', 'connect_mailbox', 'invite_team', 'customize_widget', 'set_operating_hours']),
            'completed_at' => now(),
        ];
    }
}
