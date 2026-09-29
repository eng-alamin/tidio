<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FlowTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => null, // built-in system template
            'name' => fake()->randomElement(['Welcome discount', 'Cart recovery', 'Book a demo', 'Newsletter signup']),
            'category' => fake()->randomElement(['Generate leads', 'Increase sales', 'Solve problems']),
            'description' => fake()->sentence(12),
            'canvas_json' => ['nodes' => [], 'edges' => []],
        ];
    }
}
