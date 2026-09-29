<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class NewsletterSubscriberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'subscribed_at' => now()->subDays(fake()->numberBetween(0, 500)),
            'unsubscribed_at' => null,
        ];
    }
}
