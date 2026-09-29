<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class FlowFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'created_by' => null,
            'name' => fake()->randomElement(['Welcome offer', 'Cart recovery', 'Lead capture']),
            'trigger_type' => fake()->randomElement(['page_visit', 'exit_intent', 'cart_abandonment']),
            'status' => 'active',
            'canvas_json' => ['nodes' => [], 'edges' => []],
        ];
    }
}
