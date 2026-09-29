<?php

namespace Database\Factories;

use App\Models\SuperAdmin;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdminNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'super_admin_id' => SuperAdmin::factory(),
            'notable_type' => Workspace::class,
            'notable_id' => Workspace::factory(),
            'note' => fake()->sentence(12),
        ];
    }
}
