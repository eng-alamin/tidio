<?php

namespace App\Listeners;

use App\Events\ConversationCreated;

class ApplyWorkflowRules
{
    // Settings > General > Workflows: runs every active rule for the
    // workspace, top to bottom (sort_order), applying the first match.
    // This is intentionally simple (linear scan, basic operators) — swap in
    // a proper rule engine if conditions/actions grow more complex later.
    public function handle(ConversationCreated $event): void
    {
        $conversation = $event->conversation;

        $rules = $conversation->workspace->workflowRules()
            ->where('is_active', true)
            ->where('trigger_event', 'conversation.created')
            ->orderBy('sort_order')
            ->get();

        foreach ($rules as $rule) {
            if (! $this->conditionsMatch($conversation, $rule->conditions ?? [])) {
                continue;
            }

            foreach ($rule->actions as $action) {
                $this->applyAction($conversation, $action);
            }

            break; // first matching rule wins, like most inbox routing engines
        }
    }

    protected function conditionsMatch($conversation, array $conditions): bool
    {
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $op = $condition['op'] ?? '=';
            $value = $condition['value'] ?? null;
            $actual = data_get($conversation, $field);
            
            $actual = $actual instanceof \BackedEnum ? $actual->value : $actual;
            $value = $value instanceof \BackedEnum ? $value->value : $value;

            $matches = match ($op) {
                '=' => (string) $actual === (string) $value,
                '!=' => (string) $actual !== (string) $value,
                default => false,
            };

            if (! $matches) {
                return false;
            }
        }

        return true;
    }

    protected function applyAction($conversation, array $action): void
    {
        match ($action['type'] ?? null) {
            'assign_department' => $conversation->update([
                'department_id' => \App\Models\Department::where('workspace_id', $conversation->workspace_id)
                    ->where('name', $action['value'])->value('id'),
            ]),
            'set_priority' => $conversation->update(['priority' => $action['value']]),
            'add_tag' => $conversation->tags()->syncWithoutDetaching(
                \App\Models\Tag::where('workspace_id', $conversation->workspace_id)
                    ->where('name', $action['value'])->value('id')
            ),
            default => null,
        };
    }
}
