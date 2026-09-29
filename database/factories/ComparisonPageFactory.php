<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ComparisonPageFactory extends Factory
{
    public function definition(): array
    {
        $competitor = fake()->randomElement(['Intercom', 'Zendesk', 'Gorgias', 'LiveChat', 'Manychat', 'Tawk.to']);

        return [
            'competitor_name' => $competitor,
            'slug' => 'vs/'.Str::slug($competitor),
            'comparison_table' => [
                ['feature' => 'Live Chat', 'tidio' => true, 'competitor' => true],
                ['feature' => 'AI Agent', 'tidio' => true, 'competitor' => fake()->boolean()],
                ['feature' => 'Free plan', 'tidio' => true, 'competitor' => fake()->boolean()],
            ],
            'body' => fake()->paragraph(),
        ];
    }
}
