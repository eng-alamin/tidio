<?php

namespace App\Livewire\App;

use App\Models\Tag;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SettingsTags extends Component
{
    public string $search = '';

    public bool $showCreate = false;

    #[Validate('required|string|max:50')]
    public string $name = '';

    #[Computed]
    public function tags(): Collection
    {
        return Tag::query()
            ->where('workspace_id', app('currentWorkspace')->id)
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->withCount('contacts')
            ->orderBy('name')
            ->get();
    }

    public function startCreate(): void
    {
        $this->reset(['name']);
        $this->showCreate = true;
    }

    public function create(): void
    {
        $this->validate();

        $exists = Tag::where('workspace_id', app('currentWorkspace')->id)
            ->where('name', $this->name)
            ->exists();

        if ($exists) {
            $this->addError('name', 'A tag with this name already exists.');
            return;
        }

        Tag::create([
            'workspace_id' => app('currentWorkspace')->id,
            'name' => $this->name,
        ]);

        $this->reset(['name', 'showCreate']);
        unset($this->tags);

        $this->dispatch('toast', message: "Tag \"{$this->name}\" created.");
    }

    public function cancel(): void
    {
        $this->reset(['name', 'showCreate']);
    }

    public function delete(int $tagId): void
    {
        Tag::where('workspace_id', app('currentWorkspace')->id)->where('id', $tagId)->delete();
        unset($this->tags);

        $this->dispatch('toast', message: 'Tag deleted.');
    }

    public function render()
    {
        return view('livewire.app.settings-tags')
            ->layout('layouts.app', ['title' => 'Settings']);
    }
}
