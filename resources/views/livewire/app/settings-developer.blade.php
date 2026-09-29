<div class="body">
    @include('livewire.app.settings-nav')

    <div class="content">
        <h2 style="font-size:22px;margin-bottom:14px">Developer</h2>
        <div class="subtabs">
            <a href="#" wire:click.prevent="setTab('webhooks')" class="{{ $tab === 'webhooks' ? 'on' : '' }}">Webhooks</a>
            <a href="#" wire:click.prevent="setTab('data')" class="{{ $tab === 'data' ? 'on' : '' }}">Developer data</a>
            <a href="#" wire:click.prevent="setTab('openapi')" class="{{ $tab === 'openapi' ? 'on' : '' }}">OpenAPI</a>
        </div>

        @if ($tab === 'webhooks')
            <div class="tools">
                <span style="flex:1"></span>
                <button class="btn pri" wire:click="startCreate">+ Add webhook</button>
            </div>

            @if ($showCreate)
                <div class="card" style="padding:16px;margin-bottom:16px">
                    <form wire:submit="createWebhook" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
                        <div style="flex:1;min-width:260px">
                            <label style="display:block;font-size:12px;margin-bottom:4px">Endpoint URL</label>
                            <input class="f" wire:model="url" placeholder="https://api.example.com/hooks/chat">
                            @error('url') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                        </div>
                        <div style="min-width:200px">
                            <label style="display:block;font-size:12px;margin-bottom:4px">Event</label>
                            <select class="f" wire:model="event">
                                <option value="conversation.created">New conversation</option>
                                <option value="conversation.solved">Conversation solved</option>
                                <option value="message.created">New message</option>
                                <option value="contact.created">New contact</option>
                            </select>
                        </div>
                        <button type="submit" class="btn pri">Add</button>
                        <button type="button" class="btn" wire:click="cancel">Cancel</button>
                    </form>
                </div>
            @endif

            <div class="card" style="padding:0;margin-bottom:22px">
                <table>
                    <tr><th>URL</th><th>Event</th><th>Status</th><th></th></tr>
                    @forelse ($this->webhooks as $webhook)
                        <tr wire:key="webhook-{{ $webhook->id }}">
                            <td>{{ $webhook->url }}</td>
                            <td>{{ collect($webhook->events ?? [])->map(fn ($e) => str($e)->replace('.', ' ')->headline())->implode(', ') }}</td>
                            <td>
                                <button class="pill {{ $webhook->is_active ? 'ok' : '' }}"
                                        style="border:0;cursor:pointer"
                                        wire:click="toggleActive({{ $webhook->id }})"
                                        title="Click to toggle">
                                    {{ $webhook->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td>
                                <button class="ib" wire:click="deleteWebhook({{ $webhook->id }})" wire:confirm="Remove this webhook?" aria-label="Remove webhook">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="color:var(--soft)">No webhooks yet — add your first one above.</td></tr>
                    @endforelse
                </table>
            </div>

            <h2 class="sec">API key</h2>
            <div class="card">
                <input class="f" value="{{ app('currentWorkspace')->api_key }}" readonly style="max-width:420px">
                <button class="btn" wire:click="regenerateApiKey" wire:confirm="Regenerating will invalidate the current key. Continue?">Regenerate</button>
            </div>
        @elseif ($tab === 'data')
            <div class="card" style="max-width:560px">
                <label style="margin-top:0">Project ID</label>
                <input class="f" value="loop_prj_{{ app('currentWorkspace')->id }}" readonly>
                <label>Public key</label>
                <input class="f" value="pk_live_••••••••" readonly>
            </div>
        @elseif ($tab === 'openapi')
            <div class="card">
                <p style="margin-top:0;color:var(--soft)">Use the REST API to read conversations and contacts.</p>
                <pre style="background:#0B1226;color:#E7ECF5;border:1px solid var(--line);padding:16px;border-radius:10px;overflow:auto">curl https://api.loop.example/v1/contacts \
  -H "Authorization: Bearer {{ app('currentWorkspace')->api_key }}"</pre>
            </div>
        @endif
    </div>
</div>
