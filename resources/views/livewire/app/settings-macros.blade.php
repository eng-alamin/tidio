<div class="body">
    @include('livewire.app.settings-nav')

    <div class="content">
        <h2 style="font-size:22px;margin-bottom:14px">Macros</h2>

        <div class="tools">
            <input class="f" wire:model.live.debounce.300ms="search" placeholder="Search macros…">
            <span style="flex:1"></span>
            <button class="btn pri" wire:click="startCreate">+ New macro</button>
        </div>

        @if ($showCreate)
            <div class="card" style="padding:16px;margin-bottom:16px">
                <form wire:submit="save" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
                    <div style="min-width:200px">
                        <label style="display:block;font-size:12px;margin-bottom:4px">Title</label>
                        <input class="f" wire:model="title" placeholder="e.g. Greeting">
                        @error('title') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                    </div>
                    <div style="flex:1;min-width:240px">
                        <label style="display:block;font-size:12px;margin-bottom:4px">Message</label>
                        <input class="f" wire:model="body" placeholder="Hi! Thanks for reaching out, how can I help?">
                        @error('body') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                    </div>
                    <button type="submit" class="btn pri">{{ $editingId ? 'Save' : 'Create' }}</button>
                    <button type="button" class="btn" wire:click="cancel">Cancel</button>
                </form>
            </div>
        @endif

        <div class="card" style="padding:0">
            <table>
                <tr><th>Title</th><th>Message</th><th></th></tr>
                @forelse ($this->macros as $macro)
                    <tr wire:key="macro-{{ $macro->id }}">
                        <td><code>{{ $macro->title }}</code></td>
                        <td>{{ \Illuminate\Support\Str::limit($macro->body, 80) }}</td>
                        <td style="white-space:nowrap">
                            <button class="ib" wire:click="edit({{ $macro->id }})" aria-label="Edit macro">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="ib" wire:click="delete({{ $macro->id }})" wire:confirm="Delete this macro?" aria-label="Delete macro">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="color:var(--soft)">No macros yet — create your first one above.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
</div>