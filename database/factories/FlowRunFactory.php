<?php

namespace Database\Factories;

use App\Models\Flow;
use Illuminate\Database\Eloquent\Factories\Factory;

class FlowRunFactory extends Factory
{
    public function definition(): array
    {
        return [
            'flow_id' => Flow::factory(),
            'status' => 'completed',
            'outcome' => fake()->randomElement(['lead', 'sale', 'booking', 'none']),
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
        ];
    }
}
