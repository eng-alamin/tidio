<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'channel_type' => 'widget',
            'type' => 'chat',
            'status' => fake()->randomElement(['open', 'pending', 'solved']),
            'priority' => fake()->randomElement(['low', 'normal', 'high']),
            'subject' => null,
            'last_message_at' => now(),
        ];
    }
}
