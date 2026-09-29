<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactSegmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->randomElement(['Subscribers', 'High value customers', 'Inactive 30 days']),
            'filters' => ['match' => 'all', 'rules' => [['field' => 'tag', 'op' => 'has', 'value' => 'VIP']]],
        ];
    }
}
