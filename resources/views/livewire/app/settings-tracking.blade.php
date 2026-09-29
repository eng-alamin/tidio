<div class="body">
    @include('livewire.app.settings-nav')

    <div class="content">
        <h2 style="font-size:22px;margin-bottom:14px">Tracking</h2>
        <div class="subtabs">
            <a href="#" wire:click.prevent="setTab('configure')" class="{{ $tab === 'configure' ? 'on' : '' }}">Configure</a>
            <a href="#" wire:click.prevent="setTab('events')" class="{{ $tab === 'events' ? 'on' : '' }}">Events</a>
        </div>

        @if ($tab === 'configure')
            <form wire:submit="saveConfig">
                <div class="steps">
                    <div class="step">
                        <div>
                            <b>Track page views</b>
                            <p>Record which pages visitors open</p>
                        </div>
                        <input type="checkbox" wire:model="track_page_views" style="width:20px;height:20px">
                    </div>
                    <div class="step">
                        <div>
                            <b>Track custom events</b>
                            <p>Send events from your site with the JS API</p>
                        </div>
                        <input type="checkbox" wire:model="track_custom_events" style="width:20px;height:20px">
                    </div>
                </div>
                <button type="submit" class="btn pri" style="margin-top:18px">Save changes</button>
            </form>
        @elseif ($tab === 'events')
            <div class="tools">
                <span style="flex:1"></span>
                <button class="btn pri" wire:click="startCreate">+ New event</button>
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
                                <button class="ib" wire:click="deleteEvent({{ $event->id }})" wire:confirm="Delete this event?" aria-label="Delete event">
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
