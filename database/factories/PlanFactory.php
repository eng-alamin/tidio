<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PlanFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->randomElement(['Free', 'Basic', 'Plus', 'Premium']);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 9999),
            'price_monthly' => ['Free' => 0, 'Basic' => 2900, 'Plus' => 5900, 'Premium' => 9900][$name],
            'price_yearly' => ['Free' => 0, 'Basic' => 29000, 'Plus' => 59000, 'Premium' => 99000][$name],
            'features' => ['live_chat', 'help_desk'],
            'limits' => ['operators' => 5, 'ai_conversations' => 50],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
