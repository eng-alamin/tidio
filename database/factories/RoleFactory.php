<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->randomElement(['Owner', 'Admin', 'Operator']),
            'permissions' => ['inbox.view', 'inbox.reply'],
            'is_system' => true,
        ];
    }
}
