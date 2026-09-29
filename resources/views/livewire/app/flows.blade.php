<div class="body">
    <div class="content">
        <div class="subtabs">
            <a href="#" wire:click.prevent="setTab('welcome')" class="{{ $tab === 'welcome' ? 'on' : '' }}">Welcome</a>
            <a href="#" wire:click.prevent="setTab('flows')" class="{{ $tab === 'flows' ? 'on' : '' }}">My flows</a>
            <a href="#" wire:click.prevent="setTab('templates')" class="{{ $tab === 'templates' ? 'on' : '' }}">Templates</a>
            <a href="#" wire:click.prevent="setTab('strategies')" class="{{ $tab === 'strategies' ? 'on' : '' }}">Strategies</a>
        </div>

        {{-- ===================== WELCOME ===================== --}}
        @if ($tab === 'welcome')
            <div class="card" style="max-width:640px">
                <h2 style="margin-top:0">Welcome to Flows</h2>
                <p style="color:var(--soft)">Flows are automatic conversations that greet visitors, collect leads and answer common questions.</p>
                <button class="btn pri" wire:click="openTemplates('sales')">Browse templates</button>
                <button class="btn" wire:click="setTab('flows')">My flows</button>
            </div>

        {{-- ===================== MY FLOWS ===================== --}}
        @elseif ($tab === 'flows')
            <div class="tools">
                <input class="f" wire:model.live.debounce.300ms="search" placeholder="Search flows…">
                <span style="flex:1"></span>
                <button class="btn pri" wire:click="$set('showCreate', true)">+ Create flow</button>
            </div>

            @if ($showCreate)
                <div class="card" style="padding:16px;margin-bottom:16px">
                    <form wire:submit="createFlow" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
                        <div style="flex:1;min-width:200px">
                            <label style="display:block;font-size:12px;margin-bottom:4px">Flow name</label>
                            <input class="f" wire:model="newName" placeholder="e.g. Welcome visitors">
                            @error('newName') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                        </div>
                        <div style="min-width:180px">
                            <label style="display:block;font-size:12px;margin-bottom:4px">Trigger</label>
                            <select class="f" wire:model="newTrigger">
                                <option value="page_visit">Opens site</option>
                                <option value="time_on_page">Time on page</option>
                                <option value="exit_intent">Exit intent</option>
                                <option value="cart_abandonment">Cart abandonment</option>
                                <option value="manual">Manual</option>
                            </select>
                        </div>
                        <button type="submit" class="btn pri">Create</button>
                        <button type="button" class="btn" wire:click="$set('showCreate', false)">Cancel</button>
                    </form>
                </div>
            @endif

            <div class="card" style="padding:0;margin-bottom:22px">
                <table>
                    <tr><th>Flow</th><th>Trigger</th><th>Visitors reached</th><th>Status</th><th></th></tr>
                    @forelse ($this->flows as $flow)
                        <tr wire:key="flow-{{ $flow->id }}">
                            <td>{{ $flow->name }}</td>
                            <td>
                                {{ match($flow->trigger_type->value) {
                                    'page_visit' => 'Opens site',
                                    'time_on_page' => 'Time on page',
                                    'exit_intent' => 'Exit intent',
                                    'cart_abandonment' => 'Cart abandonment',
                                    default => 'Manual',
                                } }}
                            </td>
                            <td>{{ $flow->visitors_reached }}</td>
                            <td>
                                <button class="pill {{ $flow->status->value === 'active' ? 'ok' : '' }}"
                                        style="border:0;cursor:pointer"
                                        wire:click="cycleStatus({{ $flow->id }})"
                                        title="Click to change status">
                                    {{ ucfirst($flow->status->value) }}
                                </button>
                            </td>
                            <td>
                                <button class="ib" wire:click="deleteFlow({{ $flow->id }})" wire:confirm="Delete this flow?" aria-label="Delete flow">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="color:var(--soft)">No flows yet — create your first one above.</td></tr>
                    @endforelse
                </table>
            </div>

            <h2 class="sec">Start from a template</h2>
            <div class="tpl">
                <div class="card" wire:click="openTemplates('sales')" style="cursor:pointer">
                    <i class="bi bi-graph-up-arrow"></i>
                    <p><b>Increase sales</b><br><small>Nudge visitors toward checkout</small></p>
                </div>
                <div class="card" wire:click="openTemplates('leads')" style="cursor:pointer">
                    <i class="bi bi-person-plus"></i>
                    <p><b>Generate leads</b><br><small>Collect emails with a friendly form</small></p>
                </div>
                <div class="card" wire:click="openTemplates('solve')" style="cursor:pointer">
                    <i class="bi bi-life-preserver"></i>
                    <p><b>Solve problems</b><br><small>Answer common questions instantly</small></p>
                </div>
            </div>

        {{-- ===================== TEMPLATES ===================== --}}
        @elseif ($tab === 'templates')
            <div class="subtabs sec">
                <a href="#" wire:click.prevent="setCategory('sales')" class="{{ $category === 'sales' ? 'on' : '' }}">Increase sales</a>
                <a href="#" wire:click.prevent="setCategory('leads')" class="{{ $category === 'leads' ? 'on' : '' }}">Generate leads</a>
                <a href="#" wire:click.prevent="setCategory('solve')" class="{{ $category === 'solve' ? 'on' : '' }}">Solve problems</a>
            </div>

            <div class="tpl">
                @foreach (\App\Livewire\App\Flows::TEMPLATES[$category] as $i => $template)
                    <div class="card" wire:key="tpl-{{ $category }}-{{ $i }}">
                        <i class="bi {{ $template['icon'] }}"></i>
                        <p><b>{{ $template['title'] }}</b><br><small>{{ $template['desc'] }}</small></p>
                        <button class="btn pri" wire:click="useTemplate('{{ $category }}', {{ $i }})">Use template</button>
                    </div>
                @endforeach
            </div>

        {{-- ===================== STRATEGIES ===================== --}}
        @elseif ($tab === 'strategies')
            <div class="tpl">
                @foreach (\App\Livewire\App\Flows::STRATEGIES as $i => $strategy)
                    <div class="card" wire:key="strategy-{{ $i }}">
                        <i class="bi {{ $strategy['icon'] }}"></i>
                        <p><b>{{ $strategy['title'] }}</b><br><small>{{ $strategy['desc'] }}</small></p>
                        <button class="btn pri" wire:click="useStrategy({{ $i }})">Use template</button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>