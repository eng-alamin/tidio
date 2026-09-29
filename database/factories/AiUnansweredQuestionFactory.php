<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiUnansweredQuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'question' => fake()->sentence().'?',
            'asked_count' => fake()->numberBetween(1, 10),
        ];
    }
}
