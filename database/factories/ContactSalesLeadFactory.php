<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ContactSalesLeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->companyEmail(),
            'company' => fake()->company(),
            'phone' => fake()->phoneNumber(),
            'message' => fake()->sentence(15),
            'source_page' => fake()->randomElement(['/pricing/', '/contact-sales/', '/ai-agent/']),
            'status' => 'new',
        ];
    }
}
