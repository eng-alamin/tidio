<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SAVE##??')),
            'discount_type' => 'percent',
            'discount_value' => fake()->randomElement([10, 20, 30]),
            'valid_from' => now(),
            'valid_until' => now()->addMonths(3),
            'max_redemptions' => 100,
            'times_redeemed' => 0,
            'is_active' => true,
        ];
    }
}
