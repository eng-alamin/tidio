<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkflowRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => 'Auto-assign WhatsApp to Support',
            'trigger_event' => 'conversation.created',
            'conditions' => [['field' => 'channel_type', 'op' => '=', 'value' => 'whatsapp']],
            'actions' => [['type' => 'assign_department', 'value' => 'support']],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
