<?php

namespace Database\Factories;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationMetricFactory extends Factory
{
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'first_response_at' => now()->subMinutes(5),
            'first_response_seconds' => fake()->numberBetween(30, 900),
            'resolved_at' => now(),
            'resolution_seconds' => fake()->numberBetween(300, 7200),
            'sla_breached' => false,
        ];
    }
}
