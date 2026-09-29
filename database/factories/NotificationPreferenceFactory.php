<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationPreferenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'workspace_id' => Workspace::factory(),
            'event_type' => fake()->randomElement(['new_conversation', 'mention', 'assigned_to_you']),
            'channel' => fake()->randomElement(['email', 'push', 'desktop']),
            'is_enabled' => true,
        ];
    }
}
