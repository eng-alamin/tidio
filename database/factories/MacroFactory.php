<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class MacroFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'created_by' => null,
            'title' => fake()->randomElement(['Refund policy', 'Shipping times', 'Thanks & close']),
            'body' => fake()->paragraph(),
        ];
    }
}
