<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VisitorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'contact_id' => null,
            'session_id' => Str::uuid(),
            'ip_address' => fake()->ipv4(),
            'browser' => fake()->randomElement(['Chrome', 'Safari', 'Firefox']),
            'os' => fake()->randomElement(['Windows', 'macOS', 'Android', 'iOS']),
            'location' => fake()->city().', '.fake()->country(),
            'current_page' => '/',
            'is_online' => fake()->boolean(30),
            'first_seen_at' => now()->subMinutes(fake()->numberBetween(1, 120)),
            'last_seen_at' => now(),
        ];
    }
}
