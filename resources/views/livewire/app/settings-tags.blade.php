<div class="body">
    @include('livewire.app.settings-nav')

    <div class="content">
        <h2 style="font-size:22px;margin-bottom:14px">Tags</h2>

        <div class="tools">
            <input class="f" wire:model.live.debounce.300ms="search" placeholder="Search tags…">
            <span style="flex:1"></span>
            <button class="btn pri" wire:click="startCreate">+ New tag</button>
        </div>

        @if ($showCreate)
            <div class="card" style="padding:16px;margin-bottom:16px">
                <form wire:submit="create" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
                    <div style="flex:1;min-width:200px">
                        <label style="display:block;font-size:12px;margin-bottom:4px">Tag name</label>
                        <input class="f" wire:model="name" placeholder="e.g. VIP">
                        @error('name') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                    </div>
                    <button type="submit" class="btn pri">Create</button>
                    <button type="button" class="btn" wire:click="cancel">Cancel</button>
                </form>
            </div>
        @endif

        <div class="card" style="padding:0">
            <table>
                <tr><th>Tag</th><th>Used on</th><th></th></tr>
                @forelse ($this->tags as $tag)
                    <tr wire:key="tag-{{ $tag->id }}">
                        <td><span class="pill">{{ $tag->name }}</span></td>
                        <td>{{ $tag->contacts_count }} {{ Str::plural('contact', $tag->contacts_count) }}</td>
                        <td>
                            <button class="ib" wire:click="delete({{ $tag->id }})" wire:confirm="Delete this tag?" aria-label="Delete tag">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="color:var(--soft)">No tags yet — create your first one above.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
</div>
