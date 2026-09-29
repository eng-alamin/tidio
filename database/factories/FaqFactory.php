<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FaqFactory extends Factory
{
    public function definition(): array
    {
        return [
            'question' => fake()->sentence().'?',
            'answer' => fake()->paragraph(),
            'category' => fake()->randomElement(['pricing', 'security', 'general', 'integrations']),
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}
