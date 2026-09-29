<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WebhookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'url' => fake()->url(),
            'events' => ['conversation.created', 'message.received'],
            'secret' => Str::random(32),
            'is_active' => true,
        ];
    }
}
