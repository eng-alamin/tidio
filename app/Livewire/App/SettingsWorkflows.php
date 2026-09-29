<?php

namespace App\Livewire\App;

use App\Models\Department;
use App\Models\Tag;
use App\Models\WorkflowRule;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SettingsWorkflows extends Component
{
    public bool $showCreate = false;

    public ?int $editingId = null;

    public string $name = '';

    // The listener (App\Listeners\ApplyWorkflowRules) only reacts to this event today.
    // Add more entries here only once a listener actually handles them, otherwise
    // rules get saved that never run.
    public string $trigger_event = 'conversation.created';

    // One optional condition. '' = "any conversation". Stored as
    // [{"field":"channel_type","op":"=","value":"whatsapp"}] in workflow_rules.conditions.
    public string $condition_field = '';
    public string $condition_op = '=';
    public string $condition_value = '';

    // One action. Stored as [{"type":"assign_department","value":"Support"}] in workflow_rules.actions.
    public string $action_type = 'assign_department';
    public string $action_value = '';

    public const TRIGGERS = [
        'conversation.created' => 'A new conversation starts',
    ];

    public const CONDITION_FIELDS = [
        'channel_type' => 'Channel',
        'priority' => 'Priority',
        'status' => 'Status',
    ];

    public const ACTIONS = [
        'assign_department' => 'Assign to department',
        'set_priority' => 'Set priority',
        'add_tag' => 'Add tag',
    ];

    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'trigger_event' => ['required', Rule::in(array_keys(self::TRIGGERS))],
            'condition_field' => ['nullable', Rule::in(array_keys(self::CONDITION_FIELDS))],
            'condition_op' => ['required', Rule::in(['=', '!='])],
            'condition_value' => ['required_with:condition_field', 'nullable', 'string', 'max:100'],
            'action_type' => ['required', Rule::in(array_keys(self::ACTIONS))],
            'action_value' => ['required', 'string', 'max:100'],
        ];
    }

    // Changing the field/type invalidates the previously picked value.
    public function updatedConditionField(): void
    {
        $this->condition_value = '';
    }

    public function updatedActionType(): void
    {
        $this->action_value = '';
    }

    /** Values offered for the condition value dropdown, based on the chosen field. */
    public function conditionOptions(): array
    {
        return match ($this->condition_field) {
            'channel_type' => ['widget', 'whatsapp', 'messenger', 'instagram', 'email'],
            'priority' => self::PRIORITIES,
            'status' => ['open', 'pending', 'solved', 'spam'],
            default => [],
        };
    }

    /**
     * Values offered for the action value dropdown. Department/tag actions are matched
     * by NAME in the listener, so offering only existing names avoids rules that
     * silently do nothing because of a typo.
     */
    public function actionOptions(): array
    {
        $workspaceId = app('currentWorkspace')->id;

        return match ($this->action_type) {
            'assign_department' => Department::where('workspace_id', $workspaceId)->orderBy('name')->pluck('name')->all(),
            'add_tag' => Tag::where('workspace_id', $workspaceId)->orderBy('name')->pluck('name')->all(),
            'set_priority' => self::PRIORITIES,
            default => [],
        };
    }

    public function describeWhen(WorkflowRule $rule): string
    {
        $text = self::TRIGGERS[$rule->trigger_event] ?? $rule->trigger_event;

        foreach ($rule->conditions ?? [] as $c) {
            $field = self::CONDITION_FIELDS[$c['field'] ?? ''] ?? ($c['field'] ?? '?');
            $op = ($c['op'] ?? '=') === '!=' ? 'is not' : 'is';
            $text .= " · {$field} {$op} {$c['value']}";
        }

        return $text;
    }

    public function describeThen(WorkflowRule $rule): string
    {
        return collect($rule->actions ?? [])
            ->map(fn ($a) => (self::ACTIONS[$a['type'] ?? ''] ?? ($a['type'] ?? '?')).': '.($a['value'] ?? ''))
            ->implode(', ');
    }

    #[Computed]
    public function workflows(): Collection
    {
        return WorkflowRule::query()
            ->where('workspace_id', app('currentWorkspace')->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function startCreate(): void
    {
        $this->resetForm();
        $this->showCreate = true;
    }

    public function edit(int $workflowId): void
    {
        $workflow = WorkflowRule::where('workspace_id', app('currentWorkspace')->id)->findOrFail($workflowId);

        // This form edits ONE condition and ONE action. Rules that were created
        // elsewhere with several will keep only the first of each after saving.
        $condition = $workflow->conditions[0] ?? null;
        $action = $workflow->actions[0] ?? null;

        $this->editingId = $workflow->id;
        $this->name = $workflow->name;
        $this->trigger_event = $workflow->trigger_event;
        $this->condition_field = $condition['field'] ?? '';
        $this->condition_op = $condition['op'] ?? '=';
        $this->condition_value = (string) ($condition['value'] ?? '');
        $this->action_type = $action['type'] ?? 'assign_department';
        $this->action_value = (string) ($action['value'] ?? '');
        $this->showCreate = true;
    }

    public function save(): void
    {
        $this->validate();

        // set_priority must be a real priority value, not free text.
        if ($this->action_type === 'set_priority' && ! in_array($this->action_value, self::PRIORITIES, true)) {
            $this->addError('action_value', 'Pick a valid priority.');

            return;
        }

        $workspaceId = app('currentWorkspace')->id;

        $payload = [
            'name' => $this->name,
            'trigger_event' => $this->trigger_event,
            'conditions' => $this->condition_field === ''
                ? null
                : [['field' => $this->condition_field, 'op' => $this->condition_op, 'value' => $this->condition_value]],
            'actions' => [['type' => $this->action_type, 'value' => $this->action_value]],
        ];

        if ($this->editingId) {
            WorkflowRule::where('workspace_id', $workspaceId)->findOrFail($this->editingId)->update($payload);
            $toast = "Workflow \"{$this->name}\" updated.";
        } else {
            WorkflowRule::create($payload + [
                'workspace_id' => $workspaceId,
                'is_active' => false,
                // rules run top to bottom, so new ones go to the end
                'sort_order' => (int) WorkflowRule::where('workspace_id', $workspaceId)->max('sort_order') + 1,
            ]);
            $toast = "Workflow \"{$this->name}\" created.";
        }

        $this->resetForm();
        $this->showCreate = false;
        unset($this->workflows);

        $this->dispatch('toast', message: $toast);
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showCreate = false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'name', 'trigger_event', 'condition_field', 'condition_op', 'condition_value',
            'action_type', 'action_value', 'editingId',
        ]);
        $this->resetValidation();
    }

    public function toggleActive(int $workflowId): void
    {
        $workflow = WorkflowRule::where('workspace_id', app('currentWorkspace')->id)->findOrFail($workflowId);
        $workflow->is_active = ! $workflow->is_active;
        $workflow->save();

        unset($this->workflows);
    }

    public function delete(int $workflowId): void
    {
        WorkflowRule::where('workspace_id', app('currentWorkspace')->id)->where('id', $workflowId)->delete();
        unset($this->workflows);

        $this->dispatch('toast', message: 'Workflow deleted.');
    }

    public function render()
    {
        return view('livewire.app.settings-workflows')
            ->layout('layouts.app', ['title' => 'Settings']);
    }
}