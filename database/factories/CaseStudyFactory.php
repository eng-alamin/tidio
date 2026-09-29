<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CaseStudyFactory extends Factory
{
    public function definition(): array
    {
        $customer = fake()->company();

        return [
            'customer_name' => $customer,
            'logo' => null,
            'industry' => fake()->randomElement(['Ecommerce', 'SaaS', 'Education', 'Finance', 'Travel']),
            'stat_highlight' => fake()->numberBetween(20, 90).'% increase in '.fake()->randomElement(['self-service resolution', 'qualified leads', 'conversion rate']),
            'slug' => Str::slug($customer).'-'.fake()->unique()->numberBetween(1, 9999),
            'body' => implode("\n\n", fake()->paragraphs(4)),
            'is_published' => true,
            'published_at' => now()->subDays(fake()->numberBetween(0, 300)),
        ];
    }
}
