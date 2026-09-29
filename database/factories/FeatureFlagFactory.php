<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FeatureFlagFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => null, // global by default; override per-row in seeder
            'key' => fake()->randomElement(['beta_flow_builder_v2', 'ai_copilot', 'new_inbox_ui']),
            'is_enabled' => fake()->boolean(50),
        ];
    }
}
