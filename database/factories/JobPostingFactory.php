<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class JobPostingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->randomElement(['Backend Engineer', 'Product Designer', 'Customer Success Manager', 'AI Research Engineer']),
            'department' => fake()->randomElement(['Engineering', 'Design', 'Support', 'AI']),
            'location' => fake()->randomElement(['Remote', 'Warsaw, PL', 'Remote (EU)']),
            'description' => fake()->paragraph(),
            'apply_url' => fake()->url(),
            'is_open' => true,
        ];
    }
}
