<?php

namespace Database\Factories;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_type' => 'visitor',
            'sender_id' => null,
            'body' => fake()->sentence(10),
            'attachments' => null,
            'is_private_note' => false,
            'read_at' => now(),
        ];
    }
}
