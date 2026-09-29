<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiProcedureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'title' => 'How to handle refund requests',
            'instructions' => fake()->paragraph(),
            'trigger_condition' => 'when customer asks about refunds',
            'is_active' => true,
        ];
    }
}
