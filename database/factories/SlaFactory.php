<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class SlaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => 'Standard SLA',
            'first_response_minutes' => 15,
            'resolution_minutes' => 240,
            'conditions' => null,
            'is_default' => true,
        ];
    }
}
