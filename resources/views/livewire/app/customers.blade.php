<div class="body">
    <div class="content">
        <div class="subtabs">
            <a href="#" wire:click.prevent="setTab('visitors')" class="{{ $tab === 'visitors' ? 'on' : '' }}">Visitors</a>
            <a href="#" wire:click.prevent="setTab('contacts')" class="{{ $tab === 'contacts' ? 'on' : '' }}">Contacts</a>
        </div>

        @if ($tab === 'contacts')
            <div class="tools">
                <input class="f" wire:model.live.debounce.300ms="search" placeholder="Search contacts…">
                <select class="f" style="max-width:180px" wire:model.live="tagFilter">
                    <option value="">All tags</option>
                    @foreach ($this->tags as $tag)
                        <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                    @endforeach
                </select>
                <span style="flex:1"></span>
                <button class="btn" wire:click="startImport">Import</button>
                <button class="btn pri" wire:click="$set('showAdd', true)">+ Add contact</button>
            </div>

            @if ($showImport)
                <div class="card" style="padding:16px;margin-bottom:16px">
                    <p style="margin-top:0"><b>Import contacts from CSV</b></p>
                    <p style="color:var(--soft);font-size:13px">
                        First row must be a header with any of: <code>name, email, phone, country, tags</code>
                        (tags separated by comma inside the cell, e.g. <code>Lead,Urgent</code>).
                        Rows with an email that already exists in this workspace are skipped.
                    </p>
                    <form wire:submit="importCsv" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
                        <div style="flex:1;min-width:220px">
                            <input type="file" wire:model="csvFile" accept=".csv,text/csv">
                            @error('csvFile') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                            <div wire:loading wire:target="csvFile"><small style="color:var(--soft)">Uploading…</small></div>
                        </div>
                        <button type="submit" class="btn pri" wire:loading.attr="disabled" wire:target="importCsv">Import</button>
                        <button type="button" class="btn" wire:click="cancelImport">Cancel</button>
                    </form>
                    @if ($importError)
                        <p style="margin-bottom:0;color:var(--bad)">{{ $importError }}</p>
                    @endif
                    @if ($importResult)
                        <p style="margin-bottom:0;color:var(--ok)">
                            Imported {{ $importResult['added'] }} contact(s)
                            @if ($importResult['skipped']) · skipped {{ $importResult['skipped'] }} duplicate(s) @endif
                        </p>
                    @endif
                </div>
            @endif

            @if ($showAdd)
                <div class="card" style="padding:16px;margin-bottom:16px">
                    <form wire:submit="addContact" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
                        <div style="flex:1;min-width:180px">
                            <label style="display:block;font-size:12px;margin-bottom:4px">Name</label>
                            <input class="f" wire:model="name" placeholder="Full name">
                            @error('name') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                        </div>
                        <div style="flex:1;min-width:180px">
                            <label style="display:block;font-size:12px;margin-bottom:4px">Email</label>
                            <input class="f" wire:model="email" placeholder="name@example.com">
                            @error('email') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                        </div>
                        <div style="min-width:160px">
                            <label style="display:block;font-size:12px;margin-bottom:4px">Phone</label>
                            <input class="f" wire:model="phone" placeholder="+880…">
                        </div>
                        <div style="min-width:160px">
                            <label style="display:block;font-size:12px;margin-bottom:4px">Country</label>
                            <input class="f" wire:model="country" placeholder="Bangladesh">
                        </div>
                        <div style="min-width:220px">
                            <label style="display:block;font-size:12px;margin-bottom:4px">Tags</label>
                            <div style="display:flex;flex-wrap:wrap;gap:6px;padding:9px 12px;background:var(--glass);border:1px solid var(--line);border-radius:var(--r-sm);min-height:42px;align-items:center">
                                @forelse ($this->tags as $tag)
                                    <span class="pill {{ in_array($tag->id, $selectedTags) ? 'ok' : '' }}"
                                          style="cursor:pointer;user-select:none"
                                          wire:click="toggleTag({{ $tag->id }})">
                                        {{ in_array($tag->id, $selectedTags) ? '✓ ' : '' }}{{ $tag->name }}
                                    </span>
                                @empty
                                    <small style="color:var(--soft)">No tags yet</small>
                                @endforelse
                            </div>
                        </div>
                        <button type="submit" class="btn pri">Add</button>
                        <button type="button" class="btn" wire:click="$set('showAdd', false)">Cancel</button>
                    </form>
                </div>
            @endif

            <div class="card" style="padding:0">
                <table>
                    <tr><th>Name</th><th>Email</th><th>Country</th><th>Tags</th><th>Last seen</th><th></th></tr>
                    @forelse ($this->contacts as $contact)
                        <tr wire:key="contact-{{ $contact->id }}">
                            <td><span class="av">{{ strtoupper(substr($contact->name ?: '?', 0, 1)) }}</span>{{ $contact->name ?: '—' }}</td>
                            <td>{{ $contact->email ?: '—' }}</td>
                            <td>{{ $contact->country ?: ($contact->visitors->first()?->location ?? '—') }}</td>
                            <td>
                                @forelse ($contact->tags as $tag)
                                    <span class="pill">{{ $tag->name }}</span>
                                @empty
                                    <span style="color:var(--soft)">—</span>
                                @endforelse
                            </td>
                            <td>{{ $contact->visitors->first()?->last_seen_at?->diffForHumans() ?? $contact->created_at->diffForHumans() }}</td>
                            <td>
                                <button class="ib" wire:click="deleteContact({{ $contact->id }})" wire:confirm="Delete this contact?" aria-label="Delete contact">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="color:var(--soft)">No contacts yet.</td></tr>
                    @endforelse
                </table>
            </div>
        @else
            <div class="card" style="padding:0">
                <table>
                    <tr><th>Visitor</th><th>Country</th><th>Current page</th><th>Last active</th></tr>
                    @forelse ($this->visitors as $visitor)
                        <tr wire:key="visitor-{{ $visitor->id }}">
                            <td>
                                <span class="av">V</span>
                                {{ $visitor->contact?->name ?? 'Visitor #'.$visitor->id }}
                                @if ($visitor->is_online) <span class="pill ok">Online</span> @endif
                            </td>
                            <td>{{ $visitor->location ?? '—' }}</td>
                            <td>{{ $visitor->current_page ?? '—' }}</td>
                            <td>{{ $visitor->last_seen_at?->diffForHumans() ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="color:var(--soft)">No visitors tracked yet.</td></tr>
                    @endforelse
                </table>
            </div>
        @endif
    </div>
</div>
