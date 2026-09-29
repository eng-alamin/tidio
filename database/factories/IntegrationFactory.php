<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class IntegrationFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->randomElement(['Shopify', 'WordPress', 'Zapier', 'Slack', 'HubSpot', 'Wix', 'Messenger', 'Mailchimp']);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 9999),
            'logo' => null,
            'category' => fake()->randomElement(['ecommerce', 'crm', 'marketing', 'productivity']),
            'description' => fake()->sentence(12),
            'is_featured' => fake()->boolean(30),
        ];
    }
}
