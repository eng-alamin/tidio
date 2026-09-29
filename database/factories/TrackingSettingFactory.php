<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrackingSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'provider' => 'google_analytics',
            'snippet_id' => 'G-'.strtoupper(fake()->bothify('??????????')),
            'custom_script' => null,
            'is_active' => true,
        ];
    }
}
