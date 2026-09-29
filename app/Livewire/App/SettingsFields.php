<?php

namespace App\Livewire\App;

use App\Models\CustomField;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SettingsFields extends Component
{
    public string $tab = 'custom';

    public bool $showCreate = false;

    public ?int $editingId = null;

    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('required|string')]
    public string $type = 'text';

    public bool $required = false;

    #[Computed]
    public function fields(): Collection
    {
        return CustomField::query()
            ->where('workspace_id', app('currentWorkspace')->id)
            ->latest()
            ->get();
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['custom', 'contact'], true) ? $tab : 'custom';
    }

    public function startCreate(): void
    {
        $this->reset(['name', 'type', 'required', 'editingId']);
        $this->showCreate = true;
    }

    public function edit(int $fieldId): void
    {
        $field = CustomField::where('workspace_id', app('currentWorkspace')->id)->findOrFail($fieldId);

        $this->editingId = $field->id;
        $this->name = $field->name;
        $this->type = $field->type;
        $this->required = $field->required;
        $this->showCreate = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            $field = CustomField::where('workspace_id', app('currentWorkspace')->id)->findOrFail($this->editingId);
            $field->update([
                'name' => $this->name,
                'type' => $this->type,
                'required' => $this->required,
            ]);
            $toast = "Field \"{$this->name}\" updated.";
        } else {
            CustomField::create([
                'workspace_id' => app('currentWorkspace')->id,
                'name' => $this->name,
                'type' => $this->type,
                'required' => $this->required,
            ]);
            $toast = "Field \"{$this->name}\" created.";
        }

        $this->reset(['name', 'type', 'required', 'editingId', 'showCreate']);
        unset($this->fields);

        $this->dispatch('toast', message: $toast);
    }

    public function cancel(): void
    {
        $this->reset(['name', 'type', 'required', 'editingId', 'showCreate']);
    }

    public function delete(int $fieldId): void
    {
        CustomField::where('workspace_id', app('currentWorkspace')->id)->where('id', $fieldId)->delete();
        unset($this->fields);

        $this->dispatch('toast', message: 'Field deleted.');
    }

    public function render()
    {
        return view('livewire.app.settings-fields')
            ->layout('layouts.app', ['title' => 'Settings']);
    }
}
