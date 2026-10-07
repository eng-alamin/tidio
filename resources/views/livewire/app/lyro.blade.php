<div class="body">
    <div class="content">
        <div class="subtabs">
            <a href="#" wire:click.prevent="setTab('setup')" class="{{ $tab === 'setup' ? 'on' : '' }}">Setup</a>
            <a href="#" wire:click.prevent="setTab('data-sources')" class="{{ $tab === 'data-sources' ? 'on' : '' }}">Data sources</a>
            <a href="#" wire:click.prevent="setTab('suggestions')" class="{{ $tab === 'suggestions' ? 'on' : '' }}">Suggestions</a>
            <a href="#" wire:click.prevent="setTab('guidance')" class="{{ $tab === 'guidance' ? 'on' : '' }}">Guidance</a>
            <a href="#" wire:click.prevent="setTab('handoff')" class="{{ $tab === 'handoff' ? 'on' : '' }}">Handoff</a>
            <a href="#" wire:click.prevent="setTab('actions')" class="{{ $tab === 'actions' ? 'on' : '' }}">Actions</a>
            <a href="#" wire:click.prevent="setTab('procedures')" class="{{ $tab === 'procedures' ? 'on' : '' }}">Procedures</a>
            <a href="#" wire:click.prevent="setTab('proactive')" class="{{ $tab === 'proactive' ? 'on' : '' }}">Proactive roles</a>
            <a href="#" wire:click.prevent="setTab('playground')" class="{{ $tab === 'playground' ? 'on' : '' }}">Playground</a>
            <a href="#" wire:click.prevent="setTab('channels')" class="{{ $tab === 'channels' ? 'on' : '' }}">Channels</a>
            <a href="#" wire:click.prevent="setTab('configure')" class="{{ $tab === 'configure' ? 'on' : '' }}">Configure</a>
        </div>

        @if ($tab === 'setup')
            @php($setup = $this->setup)
            {{-- Re-check every 5 s while a data source is still being read, so the step flips to Done by itself. --}}
            <div class="steps" @if ($setup['syncing'] > 0) wire:poll.5s @endif>
                <h2 style="margin-top:0">Set up Lyro AI Agent</h2>

                <div style="display:flex;align-items:center;gap:12px;margin:0 0 14px">
                    <div class="bar" style="flex:1;margin:0" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $setup['total'] }}" aria-valuenow="{{ $setup['done'] }}" aria-label="Setup progress">
                        <i style="display:block;height:100%;width:{{ $setup['percent'] }}%;background:var(--ok);border-radius:8px;transition:width .3s"></i>
                    </div>
                    <small style="color:var(--soft);white-space:nowrap">{{ $setup['done'] }} of {{ $setup['total'] }} done</small>
                    @if ($setup['live'])
                        <span class="pill ok">Live</span>
                    @else
                        <span class="pill">Not live</span>
                    @endif
                </div>

                @foreach ($setup['steps'] as $i => $step)
                    <div class="step {{ $step['state'] === 'done' ? 'done' : '' }}" wire:key="setup-step-{{ $step['key'] }}">
                        <span class="n">
                            @if ($step['state'] === 'done')
                                <i class="bi bi-check-lg" aria-hidden="true"></i><span class="sr-only" style="position:absolute;left:-9999px">Done</span>
                            @else
                                {{ $i + 1 }}
                            @endif
                        </span>

                        <div>
                            <b>{{ $step['title'] }}</b>
                            <p>{{ $step['detail'] }}</p>

                            @if ($step['key'] === 'live' && ! $setup['live'] && $setup['blockers'])
                                <ul style="margin:8px 0 0;padding-left:18px;color:var(--soft);font-size:13px">
                                    @foreach ($setup['blockers'] as $blocker)
                                        <li>{{ $blocker }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        @if ($step['key'] === 'sources')
                                @if ($step['state'] === 'done')
                                    <span class="pill ok">Done</span>
                                    <button type="button" class="btn" wire:click="setTab('data-sources')">Manage</button>
                                @elseif ($step['state'] === 'working')
                                    <span class="pill"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Syncing…</span>
                                @elseif ($step['state'] === 'attention')
                                    <button type="button" class="btn pri" wire:click="setTab('data-sources')">Fix</button>
                                @else
                                    <button type="button" class="btn pri" wire:click="setTab('data-sources')">Start</button>
                                @endif
                        @elseif ($step['key'] === 'playground')
                                @if ($step['state'] === 'done')
                                    <span class="pill ok">Done</span>
                                    <button type="button" class="btn" wire:click="setTab('playground')">Test again</button>
                                @else
                                    <button type="button" class="btn pri" wire:click="setTab('playground')">Start</button>
                                @endif
                        @elseif ($step['key'] === 'channels')
                                @if ($step['state'] === 'done')
                                    <span class="pill ok">Done</span>
                                    <button type="button" class="btn" wire:click="setTab('channels')">Review</button>
                                @elseif ($step['state'] === 'attention')
                                    <button type="button" class="btn pri" wire:click="setTab('channels')">Fix</button>
                                @else
                                    <button type="button" class="btn pri" wire:click="setTab('channels')">Start</button>
                                @endif
                        @elseif ($step['key'] === 'live')
                                @if ($setup['live'])
                                    <span class="pill ok">Live</span>
                                    <button type="button" class="btn" wire:click="pauseLyro" wire:confirm="Pause Lyro? Customers will only hear from your team." wire:loading.attr="disabled" wire:target="pauseLyro">Pause</button>
                                @else
                                    <button type="button" class="btn pri" wire:click="goLive" wire:loading.attr="disabled" wire:target="goLive" @disabled(! $setup['can_go_live']) @if (! $setup['can_go_live']) title="Finish the steps listed below first" @endif>Go live</button>
                                @endif
                        @endif
                    </div>
                @endforeach

                @foreach ($setup['warnings'] as $warning)
                    <p style="display:flex;gap:8px;align-items:flex-start;margin:8px 2px 0;color:var(--soft);font-size:13px">
                        <i class="bi bi-info-circle" aria-hidden="true" style="margin-top:2px"></i>
                        <span>
                            {{ $warning['text'] }}
                            @if ($warning['route'])
                                <a href="{{ route($warning['route']) }}"><u>{{ $warning['link'] }}</u></a>
                            @elseif ($warning['tab'])
                                <a href="#" wire:click.prevent="setTab('{{ $warning['tab'] }}')"><u>{{ $warning['link'] }}</u></a>
                            @endif
                        </span>
                    </p>
                @endforeach
            </div>
        @elseif ($tab === 'suggestions')
            <h2 class="sec" style="margin-top:0">Questions Lyro could not answer</h2>
            <div class="card" style="padding:0">
                <table>
                    <tr><th>Question</th><th>Asked</th><th></th></tr>
                    @forelse ($this->unansweredQuestions as $question)
                        <tr wire:key="uq-{{ $question->id }}">
                            <td>{{ $question->question }}</td>
                            <td>{{ $question->asked_count }} {{ \Illuminate\Support\Str::plural('time', $question->asked_count) }}</td>
                            <td style="white-space:nowrap">
                                <button type="button" class="btn pri" wire:click="startAnswer({{ $question->id }})">Add answer</button>
                                <button type="button" class="ib" wire:click="dismissQuestion({{ $question->id }})" aria-label="Dismiss">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" style="color:var(--soft)">No unanswered questions right now.</td></tr>
                    @endforelse
                </table>
            </div>

            @if ($answeringQuestionId)
                <div class="card" style="max-width:520px">
                    <label style="margin-top:0">Answer</label>
                    <textarea class="f" rows="4" wire:model="answer_text" placeholder="Write the answer Lyro should give from now on…"></textarea>
                    @error('answer_text') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                    <div style="margin-top:14px;display:flex;gap:8px">
                        <button type="button" class="btn pri" wire:click="saveAnswer">Save answer</button>
                        <button type="button" class="btn" wire:click="cancelAnswer">Cancel</button>
                    </div>
                </div>
            @endif
        @elseif ($tab === 'guidance')
            <h2 class="sec" style="margin-top:0">Guide how Lyro talks</h2>
            <form wire:submit="saveGuidance" class="card" style="max-width:640px">
                <label style="margin-top:0">Tone of voice</label>
                <select class="f" wire:model="tone">
                    <option value="friendly">Friendly</option>
                    <option value="professional">Professional</option>
                    <option value="playful">Playful</option>
                </select>
                @error('tone') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                <label>Extra instructions</label>
                <textarea class="f" rows="5" wire:model="instructions" placeholder="Keep answers short. Never promise refund dates."></textarea>
                @error('instructions') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                <br><button type="submit" class="btn pri" style="margin-top:18px">Save guidance</button>
            </form>
        @elseif ($tab === 'handoff')
            <h2 class="sec" style="margin-top:0">When Lyro hands over to a human</h2>
            <div class="steps">
                <div class="step">
                    <div><b>Customer asks for a human</b><p>Transfer immediately</p></div>
                    <input type="checkbox" wire:model.live="handoff_on_request" style="width:20px;height:20px">
                </div>
                <div class="step">
                    <div><b>Lyro is not confident</b><p>Transfer after 2 failed answers</p></div>
                    <input type="checkbox" wire:model.live="handoff_on_low_confidence" style="width:20px;height:20px">
                </div>
                <div class="step">
                    <div><b>Negative sentiment</b><p>Transfer when the customer seems upset</p></div>
                    <input type="checkbox" wire:model.live="handoff_on_negative_sentiment" style="width:20px;height:20px">
                </div>
            </div>
        @elseif ($tab === 'actions')
            <div class="subtabs sec">
                <a href="#" wire:click.prevent="setActionsTab('actions')" class="{{ $actionsTab === 'actions' ? 'on' : '' }}">Actions</a>
                <a href="#" wire:click.prevent="setActionsTab('mcps')" class="{{ $actionsTab === 'mcps' ? 'on' : '' }}">MCPs</a>
            </div>

        @if ($actionsTab === 'actions')
            <div class="tools">
                <span style="flex:1"></span>
                <button type="button" class="btn pri" wire:click="startAddAction">+ New action</button>
            </div>

            @if ($showActionForm)
                <div class="card" style="max-width:520px">
                    <label style="margin-top:0">Action name</label>
                    <input class="f" wire:model="action_name" placeholder="Check order status">
                    @error('action_name') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                    <label>Type</label>
                    <select class="f" wire:model.live="action_type">
                        <option value="webhook">API call</option>
                        <option value="internal_lookup">Built in</option>
                    </select>

                    @if ($action_type === 'webhook')
                        <label>Endpoint URL</label>
                        <input class="f" wire:model="action_endpoint_url" placeholder="https://api.example.com/orders/status">
                        @error('action_endpoint_url') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                    @endif

                    <div style="margin-top:14px;display:flex;gap:8px">
                        <button type="button" class="btn pri" wire:click="saveAction">Create</button>
                        <button type="button" class="btn" wire:click="cancelAddAction">Cancel</button>
                    </div>
                </div>
            @endif

            <div class="card" style="padding:0">
                <table>
                    <tr><th>Action</th><th>Type</th><th>Status</th><th></th></tr>
                    @forelse ($this->actions as $action)
                        <tr wire:key="action-{{ $action->id }}">
                            <td>{{ $action->name }}</td>
                            <td>{{ $action->type->value === 'webhook' ? 'API call' : 'Built in' }}</td>
                            <td>
                                <button type="button" class="pill {{ $action->is_active ? 'ok' : '' }}" style="border:0;cursor:pointer" wire:click="toggleAction({{ $action->id }})">
                                    {{ $action->is_active ? 'On' : 'Off' }}
                                </button>
                            </td>
                            <td><button type="button" class="ib" wire:click="deleteAction({{ $action->id }})" aria-label="Delete action"><i class="bi bi-trash"></i></button></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="color:var(--soft)">No actions yet — create one above.</td></tr>
                    @endforelse
                </table>
            </div>
        @elseif ($actionsTab === 'mcps')
            <div class="tools">
                <span style="flex:1"></span>
                <button type="button" class="btn pri" wire:click="startAddMcp">+ Connect MCP server</button>
            </div>

            @if ($showMcpForm)
                <div class="card" style="max-width:520px">
                    <label style="margin-top:0">Server name</label>
                    <input class="f" wire:model="mcp_name" placeholder="orders-mcp">
                    @error('mcp_name') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                    <label>Endpoint URL</label>
                    <input class="f" wire:model="mcp_endpoint_url" placeholder="https://mcp.example.com/orders">
                    @error('mcp_endpoint_url') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                    <label>Tools</label>
                    <input class="f" type="number" min="0" wire:model="mcp_tools_count" style="max-width:120px">
                    @error('mcp_tools_count') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                    <div style="margin-top:14px;display:flex;gap:8px">
                        <button type="button" class="btn pri" wire:click="saveMcp">Connect</button>
                        <button type="button" class="btn" wire:click="cancelAddMcp">Cancel</button>
                    </div>
                </div>
            @endif

            <div class="card" style="padding:0">
                <table>
                    <tr><th>Server</th><th>Tools</th><th>Status</th><th></th></tr>
                    @forelse ($this->mcps as $mcp)
                        <tr wire:key="mcp-{{ $mcp->id }}">
                            <td>{{ $mcp->name }}</td>
                            <td>{{ $mcp->config['tools_count'] ?? 0 }}</td>
                            <td>
                                <button type="button" class="pill {{ $mcp->is_active ? 'ok' : '' }}" style="border:0;cursor:pointer" wire:click="toggleMcp({{ $mcp->id }})">
                                    {{ $mcp->is_active ? 'Connected' : 'Not connected' }}
                                </button>
                            </td>
                            <td><button type="button" class="ib" wire:click="deleteMcp({{ $mcp->id }})" aria-label="Remove MCP server"><i class="bi bi-trash"></i></button></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="color:var(--soft)">No MCP servers connected yet.</td></tr>
                    @endforelse
                </table>
            </div>
        @endif
        @elseif ($tab === 'procedures')
            <div class="tools">
                <span style="flex:1"></span>
                <button type="button" class="btn pri" wire:click="startAddProcedure">+ New procedure</button>
            </div>

            @if ($showProcedureForm)
                <div class="card" style="max-width:560px">
                    <label style="margin-top:0">Procedure name</label>
                    <input class="f" wire:model="procedure_title" placeholder="Refund a purchase">
                    @error('procedure_title') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                    <label>Trigger (optional)</label>
                    <input class="f" wire:model="procedure_trigger" placeholder="Customer asks for a refund">
                    @error('procedure_trigger') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                    <label>Steps (one per line)</label>
                    <textarea class="f" rows="6" wire:model="procedure_steps" placeholder="Confirm the order number&#10;Check the refund policy&#10;Issue the refund&#10;..."></textarea>
                    @error('procedure_steps') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                    <div style="margin-top:14px;display:flex;gap:8px">
                        <button type="button" class="btn pri" wire:click="saveProcedure">Create</button>
                        <button type="button" class="btn" wire:click="cancelAddProcedure">Cancel</button>
                    </div>
                </div>
            @endif

            <div class="card" style="padding:0">
                <table>
                    <tr><th>Procedure</th><th>Steps</th><th>Status</th><th></th></tr>
                    @forelse ($this->procedures as $procedure)
                        <tr wire:key="procedure-{{ $procedure->id }}">
                            <td>{{ $procedure->title }}</td>
                            <td>{{ $this->stepCount($procedure) }}</td>
                            <td>
                                <button type="button" class="pill {{ $procedure->is_active ? 'ok' : '' }}" style="border:0;cursor:pointer" wire:click="toggleProcedure({{ $procedure->id }})">
                                    {{ $procedure->is_active ? 'On' : 'Draft' }}
                                </button>
                            </td>
                            <td><button type="button" class="ib" wire:click="deleteProcedure({{ $procedure->id }})" aria-label="Delete procedure"><i class="bi bi-trash"></i></button></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="color:var(--soft)">No procedures yet — create one above.</td></tr>
                    @endforelse
                </table>
            </div>
        @elseif ($tab === 'proactive')
            <div class="tools">
                <span style="flex:1"></span>
                <button type="button" class="btn pri" wire:click="startAddRole">+ New role</button>
            </div>

            @if ($showRoleForm)
                <div class="card" style="max-width:520px">
                    <label style="margin-top:0">Role name</label>
                    <input class="f" wire:model="role_name" placeholder="Sales assistant">
                    @error('role_name') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                    <label>Goal</label>
                    <input class="f" wire:model="role_goal" placeholder="Help visitors choose a plan">
                    @error('role_goal') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                    <div style="margin-top:14px;display:flex;gap:8px">
                        <button type="button" class="btn pri" wire:click="saveRole">Create</button>
                        <button type="button" class="btn" wire:click="cancelAddRole">Cancel</button>
                    </div>
                </div>
            @endif

            <div class="card" style="padding:0">
                <table>
                    <tr><th>Role</th><th>Goal</th><th>Status</th><th></th></tr>
                    @forelse ($this->proactiveRoles as $role)
                        <tr wire:key="role-{{ $role->id }}">
                            <td>{{ $role->name }}</td>
                            <td>{{ $role->goal }}</td>
                            <td>
                                <button type="button" class="pill {{ $role->is_active ? 'ok' : '' }}" style="border:0;cursor:pointer" wire:click="toggleRole({{ $role->id }})">
                                    {{ $role->is_active ? 'On' : 'Off' }}
                                </button>
                            </td>
                            <td><button type="button" class="ib" wire:click="deleteRole({{ $role->id }})" aria-label="Delete role"><i class="bi bi-trash"></i></button></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="color:var(--soft)">No proactive roles yet — create one above.</td></tr>
                    @endforelse
                </table>
            </div>
        @elseif ($tab === 'playground')
            <div class="subtabs sec">
                <a href="#" wire:click.prevent="setPlaygroundChannel('live')" class="{{ $playgroundChannel === 'live' ? 'on' : '' }}">Live chat</a>
                <a href="#" wire:click.prevent="setPlaygroundChannel('email')" class="{{ $playgroundChannel === 'email' ? 'on' : '' }}">Email</a>
            </div>

            @unless ($this->aiEngineAvailable)
                <div class="card" style="border-color:var(--bad);margin-bottom:14px">
                    <b style="color:var(--bad)">AI engine not connected</b>
                    <p style="margin:4px 0 0;color:var(--soft);font-size:13px">Set <code>ANTHROPIC_API_KEY</code> in your <code>.env</code> to enable real replies. Lyro will otherwise say so plainly instead of pretending to answer.</p>
                </div>
            @endunless

            <div class="two">
                <div class="card" style="min-height:420px;display:flex;flex-direction:column">
                    <div class="msgs" style="flex:1;overflow-y:auto">
                        @forelse ($playgroundMessages as $m)
                            <div class="m {{ $m['from'] === 'me' ? 'me' : '' }}">{{ $m['text'] }}</div>
                        @empty
                            <p style="color:var(--soft);font-size:13px">Ask a test question below to see how Lyro would reply.</p>
                        @endforelse
                    </div>
                    <form wire:submit="sendTestMessage" class="reply" style="padding:0;border:0;background:none;display:flex;gap:8px">
                        <input class="f" wire:model="playground_question" placeholder="Ask Lyro a test question…" style="flex:1" wire:loading.attr="disabled" wire:target="sendTestMessage">
                        <button type="submit" class="btn pri" wire:loading.attr="disabled" wire:target="sendTestMessage">
                            <span wire:loading.remove wire:target="sendTestMessage">Send</span>
                            <span wire:loading wire:target="sendTestMessage">Thinking…</span>
                        </button>
                    </form>
                    @error('playground_question') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                    @if (count($playgroundMessages))
                        <button type="button" class="btn" style="align-self:flex-start;margin-top:8px" wire:click="clearPlayground">Clear</button>
                    @endif
                </div>
                <div class="card">
                    <b>Test settings</b>
                    <label>Channel</label>
                    <select class="f" wire:model.live="playgroundChannel">
                        <option value="live">Live chat</option>
                        <option value="email">Email</option>
                    </select>
                    <label>Sources used</label>
                    <p style="color:var(--soft);font-size:13px">
                        @forelse ($this->playgroundSources as $source)
                            {{ \Illuminate\Support\Str::limit($source->source, 40) }}<br>
                        @empty
                            No synced data sources yet.
                        @endforelse
                    </p>
                </div>
            </div>
        @elseif ($tab === 'channels')
            <div class="subtabs sec">
                <a href="#" wire:click.prevent="setChannelsTab('live')" class="{{ $channelsTab === 'live' ? 'on' : '' }}">Live conversations</a>
                <a href="#" wire:click.prevent="setChannelsTab('emails')" class="{{ $channelsTab === 'emails' ? 'on' : '' }}">Emails</a>
            </div>

            @if ($channelsTab === 'live')
                <div class="steps">
                    <div class="step">
                        <div><b>Answer live conversations</b><p>Lyro replies on your chat widget</p></div>
                        <input type="checkbox" wire:model.live="live_answer_enabled" style="width:20px;height:20px">
                    </div>
                    <div class="step">
                        <div><b>Only outside operating hours</b><p>Turn off Lyro when your team is online</p></div>
                        <input type="checkbox" wire:model.live="live_outside_hours_only" style="width:20px;height:20px">
                    </div>
                </div>
            @else
                <div class="steps">
                    <div class="step">
                        <div><b>Answer emails</b><p>Lyro drafts and sends replies to tickets</p></div>
                        <input type="checkbox" wire:model.live="email_answer_enabled" style="width:20px;height:20px">
                    </div>
                    <div class="step">
                        <div><b>Draft only</b><p>Operators approve before sending</p></div>
                        <input type="checkbox" wire:model.live="email_draft_only" style="width:20px;height:20px">
                    </div>
                </div>
            @endif
        @elseif ($tab === 'configure')
            <div class="subtabs sec">
                <a href="#" wire:click.prevent="setConfigureTab('general')" class="{{ $configureTab === 'general' ? 'on' : '' }}">General</a>
                <a href="#" wire:click.prevent="setConfigureTab('audience')" class="{{ $configureTab === 'audience' ? 'on' : '' }}">Audience</a>
                <a href="#" wire:click.prevent="setConfigureTab('copilot')" class="{{ $configureTab === 'copilot' ? 'on' : '' }}">Copilot</a>
            </div>

            @if ($configureTab === 'general')
                <div class="card" style="max-width:600px">
                    <label style="margin-top:0">Agent name</label>
                    <input class="f" wire:model="agent_name">
                    @error('agent_name') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                    <label>Language</label>
                    <select class="f" wire:model="default_language">
                        <option value="auto">Auto-detect</option>
                        <option value="en">English</option>
                        <option value="bn">বাংলা</option>
                    </select>

                    <label>Tone</label>
                    <select class="f" wire:model="tone">
                        <option value="friendly">Friendly</option>
                        <option value="professional">Professional</option>
                        <option value="playful">Playful</option>
                    </select>

                    <br>
                    <button type="button" class="btn pri" style="margin-top:18px" wire:click="saveConfigureGeneral">Save</button>
                </div>
            @elseif ($configureTab === 'audience')
                <div class="card" style="max-width:600px">
                    <label style="margin-top:0">Answer for</label>
                    <select class="f" wire:model.live="audience_answer_for">
                        <option value="everyone">Everyone</option>
                        <option value="logged_in">Logged-in customers</option>
                        <option value="specific_countries">Specific countries</option>
                    </select>

                    @if ($audience_answer_for === 'specific_countries')
                        <label>Countries</label>
                        <select class="f" wire:model="audience_countries" multiple size="8" aria-label="Countries Lyro answers for">
                            @foreach ($this->countryOptions as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        <small style="color:var(--soft);display:block;margin-top:4px">
                            Hold Ctrl / Cmd to pick several. Lyro answers only visitors detected in these countries; visitors whose country can't be detected are left to your team.
                        </small>
                        @error('audience_countries') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                    @endif

                    <label>Exclude tag</label>
                    <input class="f" wire:model="audience_exclude_tag" placeholder="VIP">
                    @error('audience_exclude_tag') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                    <br>
                    <button type="button" class="btn pri" style="margin-top:18px" wire:click="saveAudience">Save</button>
                </div>
            @else
                <div class="steps">
                    <div class="step">
                        <div><b>Suggest replies to operators</b><p>Copilot drafts answers in the inbox</p></div>
                        <input type="checkbox" wire:model.live="copilot_suggest_replies" style="width:20px;height:20px">
                    </div>
                    <div class="step">
                        <div><b>Summarize conversations</b><p>Add a short summary when a chat is solved</p></div>
                        <input type="checkbox" wire:model.live="copilot_summarize" style="width:20px;height:20px">
                    </div>
                </div>
            @endif
        @else

        <h2 class="sec" style="margin-top:0">Teach Lyro about your business</h2>

        <div class="src">
            <div class="card" style="cursor:pointer" wire:click="startAdd('url')">
                <i class="bi bi-globe"></i>
                <p><b>Website</b><br><small>Reads the page and the pages it links to</small></p>
            </div>
            <div class="card" style="cursor:pointer" wire:click="startAdd('pdf')">
                <i class="bi bi-file-earmark-text"></i>
                <p><b>Files</b><br><small>PDF documents</small></p>
            </div>
            <div class="card" style="cursor:pointer" wire:click="startAdd('faq')">
                <i class="bi bi-question-circle"></i>
                <p><b>Q&amp;A</b><br><small>Write answers by hand</small></p>
            </div>
        </div>

        @if ($showAddForm)
            <div class="card" style="max-width:480px">
                @if ($addingType === 'pdf')
                    <label style="margin-top:0">PDF file</label>
                    <input class="f" type="file" wire:model="pdf_upload" accept="application/pdf,.pdf">
                    <div wire:loading wire:target="pdf_upload" style="color:var(--soft);font-size:13px;margin-top:6px">Uploading…</div>
                    @error('pdf_upload') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                    <small style="display:block;margin-top:6px;color:var(--soft)">Up to 10 MB. Scanned pages (pictures of text) can't be read.</small>
                @else
                    <label style="margin-top:0">
                        {{ match($addingType) { 'url' => 'Page URL', 'faq' => 'Question' } }}
                    </label>
                    <input class="f" wire:model="new_source" placeholder="{{ match($addingType) { 'url' => 'https://yoursite.com/pricing', 'faq' => 'How do I reset my password?' } }}">
                    @error('new_source') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                    @if ($addingType === 'url')
                        <small style="display:block;margin-top:6px;color:var(--soft)">Lyro reads this page and the pages of the same website it links to. A link straight to a PDF works too.</small>
                    @endif
                @endif

                <div style="margin-top:14px;display:flex;gap:8px">
                    <button type="button" class="btn pri" wire:click="addSource" wire:loading.attr="disabled" wire:target="addSource,pdf_upload">Add source</button>
                    <button type="button" class="btn" wire:click="cancelAdd">Cancel</button>
                </div>
            </div>
        @endif

        <div class="card" style="padding:0" @if ($this->dataSources->contains(fn ($s) => in_array($s->status->value, ['pending', 'syncing'], true))) wire:poll.3s @endif>
            <table>
                <tr><th>Source</th><th>Type</th><th>Status</th><th>Added</th><th></th></tr>
                @forelse ($this->dataSources as $source)
                    <tr wire:key="source-{{ $source->id }}">
                        <td>
                            {{ $source->title ?: $source->source }}
                            @if ($source->title && $source->title !== $source->source)
                                <br><small style="color:var(--soft)">{{ \Illuminate\Support\Str::limit($source->source, 60) }}</small>
                            @endif
                            @if ($source->pages_count > 1)
                                <br><small style="color:var(--soft)">{{ $source->pages_count }} pages read</small>
                            @endif
                            @if ($source->status->value === 'failed' && $source->error)
                                <br><small style="color:var(--bad)">{{ $source->error }}</small>
                            @endif
                        </td>
                        <td>{{ match($source->type->value) { 'url' => 'Website', 'pdf' => 'File', 'faq' => 'Q&A', 'help_center' => 'Help center' } }}</td>
                        <td>
                            <span class="pill {{ $source->status->value === 'synced' ? 'ok' : '' }}">
                                {{ match($source->status->value) { 'synced' => 'Ready', 'syncing' => 'Syncing', 'failed' => 'Failed', default => 'Processing' } }}
                            </span>
                        </td>
                        <td>{{ $source->created_at->format('M j') }}</td>
                        <td style="white-space:nowrap">
                            @if ($source->type->value !== 'faq')
                                <button type="button" class="ib" wire:click="resyncSource({{ $source->id }})" aria-label="Read again" title="Read again" @disabled($source->status->value === 'syncing')><i class="bi bi-arrow-clockwise"></i></button>
                            @endif
                            <button type="button" class="ib" wire:click="deleteSource({{ $source->id }})" aria-label="Remove source"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="color:var(--soft)">No data sources yet — add one above.</td></tr>
                @endforelse
            </table>
        </div>
        @endif
    </div>
</div>