<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomFieldFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->randomElement(['Order ID', 'Plan Type', 'Company Size']),
            'type' => 'text',
            'applies_to' => 'contact',
            'options' => null,
        ];
    }
}
