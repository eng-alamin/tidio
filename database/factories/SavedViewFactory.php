<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class SavedViewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'user_id' => null,
            'name' => fake()->randomElement(['My WhatsApp queue', 'Unassigned', 'VIP customers']),
            'filters' => ['status' => 'open'],
        ];
    }
}
