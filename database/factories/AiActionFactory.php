<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiActionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => 'Look up order status',
            'type' => 'internal_lookup',
            'endpoint_url' => null,
            'config' => ['parameters' => ['order_id']],
            'is_active' => true,
        ];
    }
}
