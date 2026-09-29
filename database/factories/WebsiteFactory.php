<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WebsiteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'domain' => fake()->domainName(),
            'widget_key' => Str::uuid(),
            'installed_at' => now(),
        ];
    }
}
