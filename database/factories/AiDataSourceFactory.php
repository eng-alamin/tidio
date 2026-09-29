<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiDataSourceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'type' => 'url',
            'source' => fake()->url(),
            'status' => 'synced',
            'last_synced_at' => now(),
        ];
    }
}
