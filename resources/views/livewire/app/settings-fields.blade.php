<div class="body">
    @include('livewire.app.settings-nav')

    <div class="content">
        <h2 style="font-size:22px;margin-bottom:14px">Fields</h2>
        <div class="subtabs">
            <a href="#" wire:click.prevent="setTab('custom')" class="{{ $tab === 'custom' ? 'on' : '' }}">Custom fields</a>
            <a href="#" wire:click.prevent="setTab('contact')" class="{{ $tab === 'contact' ? 'on' : '' }}">Contact properties</a>
        </div>

        @if ($tab === 'custom')
            <div class="tools">
                <span style="flex:1"></span>
                <button class="btn pri" wire:click="startCreate">+ New field</button>
            </div>

            @if ($showCreate)
                <div class="card" style="padding:16px;margin-bottom:16px">
                    <form wire:submit="save" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
                        <div style="flex:1;min-width:200px">
                            <label style="display:block;font-size:12px;margin-bottom:4px">Field name</label>
                            <input class="f" wire:model="name" placeholder="e.g. Company size">
                            @error('name') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                        </div>
                        <div style="min-width:160px">
                            <label style="display:block;font-size:12px;margin-bottom:4px">Type</label>
                            <select class="f" wire:model="type">
                                <option value="text">Text</option>
                                <option value="dropdown">Dropdown</option>
                                <option value="date">Date</option>
                                <option value="number">Number</option>
                            </select>
                        </div>
                        <label style="display:flex;align-items:center;gap:6px;min-width:120px">
                            <input type="checkbox" wire:model="required" style="width:18px;height:18px">
                            Required
                        </label>
                        <button type="submit" class="btn pri">{{ $editingId ? 'Save' : 'Create' }}</button>
                        <button type="button" class="btn" wire:click="cancel">Cancel</button>
                    </form>
                </div>
            @endif

            <div class="card" style="padding:0">
                <table>
                    <tr><th>Field</th><th>Type</th><th>Required</th><th></th></tr>
                    @forelse ($this->fields as $field)
                        <tr wire:key="field-{{ $field->id }}">
                            <td>{{ $field->name }}</td>
                            <td>{{ ucfirst($field->type) }}</td>
                            <td><span class="pill {{ $field->required ? 'ok' : '' }}">{{ $field->required ? 'Yes' : 'No' }}</span></td>
                            <td style="white-space:nowrap">
                                <button class="ib" wire:click="edit({{ $field->id }})" aria-label="Edit field">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="ib" wire:click="delete({{ $field->id }})" wire:confirm="Delete this field?" aria-label="Delete field">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="color:var(--soft)">No custom fields yet — create your first one above.</td></tr>
                    @endforelse
                </table>
            </div>
        @elseif ($tab === 'contact')
            <div class="card" style="padding:0">
                <table>
                    <tr><th>Property</th><th>Type</th><th>Source</th></tr>
                    <tr><td>Name</td><td>Text</td><td>Built in</td></tr>
                    <tr><td>Email</td><td>Email</td><td>Built in</td></tr>
                    <tr><td>Phone</td><td>Phone</td><td>Built in</td></tr>
                    <tr><td>Country</td><td>Text</td><td>Detected</td></tr>
                </table>
            </div>
        @endif
    </div>
</div>
