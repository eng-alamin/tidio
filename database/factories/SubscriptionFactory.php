<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'plan_id' => Plan::factory(),
            'plan_name' => 'Free',
            'status' => 'trialing',
            'seats' => 1,
            'renews_at' => now()->addDays(7),
        ];
    }
}
