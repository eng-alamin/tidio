<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ChannelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'type' => fake()->randomElement(['whatsapp', 'messenger', 'instagram', 'email']),
            'credentials' => ['token' => Str::random(32)],
            'status' => 'connected',
            'connected_at' => now(),
        ];
    }
}
