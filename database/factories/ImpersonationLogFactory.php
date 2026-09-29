<?php

namespace Database\Factories;

use App\Models\SuperAdmin;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImpersonationLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'super_admin_id' => SuperAdmin::factory(),
            'workspace_id' => Workspace::factory(),
            'user_id' => User::factory(),
            'ip_address' => fake()->ipv4(),
            'started_at' => now()->subMinutes(15),
            'ended_at' => now(),
        ];
    }
}
