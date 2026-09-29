<div class="body">
    @include('livewire.app.settings-nav')

    <div class="content">
        <h2 style="font-size:22px;margin-bottom:14px">Workflows</h2>

        <div class="tools">
            <span style="flex:1"></span>
            <button class="btn pri" wire:click="startCreate">+ New workflow</button>
        </div>

        @if ($showCreate)
            <div class="card" style="padding:16px;margin-bottom:16px">
                <form wire:submit="save" style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end">
                    <div style="min-width:200px">
                        <label style="display:block;font-size:12px;margin-bottom:4px">Workflow name</label>
                        <input class="f" wire:model="name" placeholder="e.g. Route WhatsApp to Support">
                        @error('name') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                    </div>

                    <div style="min-width:220px">
                        <label style="display:block;font-size:12px;margin-bottom:4px">When</label>
                        <select class="f" wire:model="trigger_event">
                            @foreach (\App\Livewire\App\SettingsWorkflows::TRIGGERS as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('trigger_event') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                    </div>

                    <div style="min-width:160px">
                        <label style="display:block;font-size:12px;margin-bottom:4px">If (optional)</label>
                        <select class="f" wire:model.live="condition_field">
                            <option value="">Any conversation</option>
                            @foreach (\App\Livewire\App\SettingsWorkflows::CONDITION_FIELDS as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('condition_field') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                    </div>

                    @if ($condition_field !== '')
                        <div style="min-width:110px">
                            <label style="display:block;font-size:12px;margin-bottom:4px">Operator</label>
                            <select class="f" wire:model="condition_op">
                                <option value="=">is</option>
                                <option value="!=">is not</option>
                            </select>
                        </div>
                        <div style="min-width:160px">
                            <label style="display:block;font-size:12px;margin-bottom:4px">Value</label>
                            <select class="f" wire:model="condition_value">
                                <option value="">Select…</option>
                                @foreach ($this->conditionOptions() as $option)
                                    <option value="{{ $option }}">{{ ucfirst($option) }}</option>
                                @endforeach
                            </select>
                            @error('condition_value') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                        </div>
                    @endif

                    <div style="min-width:180px">
                        <label style="display:block;font-size:12px;margin-bottom:4px">Then</label>
                        <select class="f" wire:model.live="action_type">
                            @foreach (\App\Livewire\App\SettingsWorkflows::ACTIONS as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('action_type') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                    </div>

                    <div style="min-width:180px">
                        <label style="display:block;font-size:12px;margin-bottom:4px">
                            {{ match ($action_type) { 'assign_department' => 'Department', 'add_tag' => 'Tag', default => 'Priority' } }}
                        </label>
                        <select class="f" wire:model="action_value">
                            <option value="">Select…</option>
                            @foreach ($this->actionOptions() as $option)
                                <option value="{{ $option }}">{{ $action_type === 'set_priority' ? ucfirst($option) : $option }}</option>
                            @endforeach
                        </select>
                        @error('action_value') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                        @if (in_array($action_type, ['assign_department', 'add_tag'], true) && count($this->actionOptions()) === 0)
                            <small style="color:var(--soft)">
                                No {{ $action_type === 'add_tag' ? 'tags' : 'departments' }} yet — create one first in Settings.
                            </small>
                        @endif
                    </div>

                    <button type="submit" class="btn pri">{{ $editingId ? 'Save' : 'Create' }}</button>
                    <button type="button" class="btn" wire:click="cancel">Cancel</button>
                </form>
            </div>
        @endif

        <div class="card" style="padding:0">
            <table>
                <tr><th>Workflow</th><th>When</th><th>Then</th><th>Status</th><th></th></tr>
                @forelse ($this->workflows as $workflow)
                    <tr wire:key="workflow-{{ $workflow->id }}">
                        <td>{{ $workflow->name }}</td>
                        <td>{{ $this->describeWhen($workflow) }}</td>
                        <td>{{ $this->describeThen($workflow) }}</td>
                        <td>
                            <button class="pill {{ $workflow->is_active ? 'ok' : '' }}"
                                    style="border:0;cursor:pointer"
                                    wire:click="toggleActive({{ $workflow->id }})"
                                    title="Click to toggle">
                                {{ $workflow->is_active ? 'On' : 'Off' }}
                            </button>
                        </td>
                        <td style="white-space:nowrap">
                            <button class="ib" wire:click="edit({{ $workflow->id }})" aria-label="Edit workflow">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="ib" wire:click="delete({{ $workflow->id }})" wire:confirm="Delete this workflow?" aria-label="Delete workflow">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="color:var(--soft)">No workflows yet — create your first one above.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
</div>