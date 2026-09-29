<?php

namespace Database\Factories;

use App\Models\SuperAdmin;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnnouncementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'created_by' => SuperAdmin::factory(),
            'title' => 'Scheduled maintenance',
            'body' => fake()->sentence(15),
            'audience' => 'all',
            'starts_at' => now(),
            'ends_at' => now()->addDays(2),
            'is_active' => true,
        ];
    }
}
