<?php

namespace Database\Factories;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiConversationMetaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'resolved_by_ai' => fake()->boolean(60),
            'confidence_score' => fake()->randomFloat(2, 40, 99),
            'handed_off_at' => null,
        ];
    }
}
