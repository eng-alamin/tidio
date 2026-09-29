<?php

namespace App\Livewire\App;

use App\Models\Macro;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SettingsMacros extends Component
{
    public string $search = '';

    public bool $showCreate = false;

    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|string|max:5000')]
    public string $body = '';

    #[Computed]
    public function macros(): Collection
    {
        return Macro::query()
            ->where('workspace_id', app('currentWorkspace')->id)
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                  ->orWhere('body', 'like', '%'.$this->search.'%');
            }))
            ->latest()
            ->get();
    }

    public function startCreate(): void
    {
        $this->reset(['title', 'body', 'editingId']);
        $this->showCreate = true;
    }

    public function edit(int $macroId): void
    {
        $macro = Macro::where('workspace_id', app('currentWorkspace')->id)->findOrFail($macroId);

        $this->editingId = $macro->id;
        $this->title = $macro->title ?? '';
        $this->body = $macro->body ?? '';
        $this->showCreate = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            $macro = Macro::where('workspace_id', app('currentWorkspace')->id)->findOrFail($this->editingId);
            $macro->update(['title' => $this->title, 'body' => $this->body]);
            $toast = "Macro \"{$this->title}\" updated.";
        } else {
            Macro::create([
                'workspace_id' => app('currentWorkspace')->id,
                'created_by' => auth()->id(),
                'title' => $this->title,
                'body' => $this->body,
            ]);
            $toast = "Macro \"{$this->title}\" created.";
        }

        $this->reset(['title', 'body', 'editingId', 'showCreate']);
        unset($this->macros);

        $this->dispatch('toast', message: $toast);
    }

    public function cancel(): void
    {
        $this->reset(['title', 'body', 'editingId', 'showCreate']);
    }

    public function delete(int $macroId): void
    {
        Macro::where('workspace_id', app('currentWorkspace')->id)->where('id', $macroId)->delete();
        unset($this->macros);

        $this->dispatch('toast', message: 'Macro deleted.');
    }

    public function render()
    {
        return view('livewire.app.settings-macros')
            ->layout('layouts.app', ['title' => 'Settings']);
    }
}