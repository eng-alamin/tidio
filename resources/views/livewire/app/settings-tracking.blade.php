<div class="body">
    @include('livewire.app.settings-nav')

    <div class="content">
        <h2 style="font-size:22px;margin-bottom:14px">Tracking</h2>
        <div class="subtabs">
            <a href="#" wire:click.prevent="setTab('configure')" class="{{ $tab === 'configure' ? 'on' : '' }}">Configure</a>
            <a href="#" wire:click.prevent="setTab('events')" class="{{ $tab === 'events' ? 'on' : '' }}">Events</a>
        </div>

        @if ($tab === 'configure')
            <div class="tools">
                <span style="flex:1"></span>
                <button type="button" class="btn pri" wire:click="startAddProvider">+ Connect provider</button>
            </div>

            @if ($showProviderForm)
                <div class="card" style="max-width:520px">
                    <label style="margin-top:0">Provider</label>
                    <select class="f" wire:model.live="provider">
                        <option value="google_analytics">Google Analytics</option>
                        <option value="facebook_pixel">Facebook Pixel</option>
                        <option value="gtm">Google Tag Manager</option>
                        <option value="custom">Custom script</option>
                    </select>
                    @error('provider') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                    @if ($provider === 'custom')
                        <label>Custom script</label>
                        <textarea class="f" rows="5" wire:model="custom_script" placeholder="<script>...</script>"></textarea>
                        @error('custom_script') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                    @else
                        <label>
                            @if ($provider === 'google_analytics') Measurement ID
                            @elseif ($provider === 'facebook_pixel') Pixel ID
                            @else Container ID @endif
                        </label>
                        <input class="f" wire:model="snippet_id" placeholder="{{ $provider === 'gtm' ? 'GTM-XXXXXXX' : ($provider === 'facebook_pixel' ? '123456789012345' : 'G-XXXXXXXXXX') }}">
                        @error('snippet_id') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                    @endif

                    <div style="margin-top:14px;display:flex;gap:8px">
                        <button type="button" class="btn pri" wire:click="saveProvider">{{ $editingId ? 'Save' : 'Connect' }}</button>
                        <button type="button" class="btn" wire:click="cancelProvider">Cancel</button>
                    </div>
                </div>
            @endif

            <div class="card" style="padding:0">
                <table>
                    <tr><th>Provider</th><th>ID / Script</th><th>Status</th><th></th></tr>
                    @forelse ($this->providers as $setting)
                        <tr wire:key="tracking-{{ $setting->id }}">
                            <td>
                                {{ match($setting->provider->value) {
                                    'google_analytics' => 'Google Analytics',
                                    'facebook_pixel' => 'Facebook Pixel',
                                    'gtm' => 'Google Tag Manager',
                                    default => 'Custom script',
                                } }}
                            </td>
                            <td>
                                @if ($setting->provider->value === 'custom')
                                    <code>{{ \Illuminate\Support\Str::limit($setting->custom_script, 40) }}</code>
                                @else
                                    <code>{{ $setting->snippet_id }}</code>
                                @endif
                            </td>
                            <td>
                                <button type="button" class="pill {{ $setting->is_active ? 'ok' : '' }}" style="border:0;cursor:pointer" wire:click="toggleProvider({{ $setting->id }})">
                                    {{ $setting->is_active ? 'Active' : 'Paused' }}
                                </button>
                            </td>
                            <td style="white-space:nowrap">
                                <button type="button" class="ib" wire:click="editProvider({{ $setting->id }})" aria-label="Edit provider"><i class="bi bi-pencil"></i></button>
                                <button type="button" class="ib" wire:click="deleteProvider({{ $setting->id }})" wire:confirm="Remove this tracking provider?" aria-label="Remove provider"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="color:var(--soft)">No tracking providers connected yet.</td></tr>
                    @endforelse
                </table>
            </div>
        @elseif ($tab === 'events')
            <div class="tools">
                <span style="flex:1"></span>
                <button type="button" class="btn pri" wire:click="startCreate">+ New event</button>
            </div>

            @if ($showCreate)
                <div class="card" style="padding:16px;margin-bottom:16px">
                    <form wire:submit="createEvent" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
                        <div style="flex:1;min-width:200px">
                            <label style="display:block;font-size:12px;margin-bottom:4px">Event name</label>
                            <input class="f" wire:model="name" placeholder="e.g. Viewed pricing">
                            @error('name') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                        </div>
                        <div style="flex:1;min-width:200px">
                            <label style="display:block;font-size:12px;margin-bottom:4px">Trigger</label>
                            <input class="f" wire:model="trigger" placeholder="e.g. Page /pricing">
                            @error('trigger') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                        </div>
                        <button type="submit" class="btn pri">Create</button>
                        <button type="button" class="btn" wire:click="cancel">Cancel</button>
                    </form>
                </div>
            @endif

            <div class="card" style="padding:0">
                <table>
                    <tr><th>Event</th><th>Trigger</th><th>Count (30d)</th><th></th></tr>
                    @forelse ($this->events as $event)
                        <tr wire:key="event-{{ $event->id }}">
                            <td>{{ $event->name }}</td>
                            <td>{{ $event->trigger }}</td>
                            <td>{{ $event->count_30d }}</td>
                            <td>
                                <button type="button" class="ib" wire:click="deleteEvent({{ $event->id }})" wire:confirm="Delete this event?" aria-label="Delete event">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="color:var(--soft)">No custom events yet — create your first one above.</td></tr>
                    @endforelse
                </table>
            </div>
        @endif
    </div>
</div>
