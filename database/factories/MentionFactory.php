<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MentionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'message_id' => Message::factory(),
            'mentioned_user_id' => User::factory(),
            'read_at' => null,
        ];
    }
}
