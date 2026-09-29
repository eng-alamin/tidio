<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->randomElement(['VIP', 'Lead', 'Refund', 'Bug Report', 'Urgent']),
            'color' => fake()->randomElement(['#4C63D2', '#E74C3C', '#2ECC71', '#F39C12']),
        ];
    }
}
