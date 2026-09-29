<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TestimonialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quote' => fake()->sentence(20),
            'author_name' => fake()->name(),
            'author_company' => fake()->company(),
            'rating' => fake()->numberBetween(4, 5),
            'source' => fake()->randomElement(['g2', 'capterra', 'shopify', 'wordpress', 'direct']),
            'is_featured' => fake()->boolean(40),
        ];
    }
}
