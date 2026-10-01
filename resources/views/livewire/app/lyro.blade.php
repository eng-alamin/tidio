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
            <div class="steps">
                <h2 style="margin-top:0">Set up Lyro AI Agent</h2>

                <div class="step {{ $this->dataSources->isNotEmpty() ? 'done' : '' }}">
                    @if ($this->dataSources->isNotEmpty())
                        <span class="n"><i class="bi bi-check-lg"></i></span>
                    @else
                        <span class="n">1</span>
                    @endif
                    <div><b>Add data sources</b><p>Teach Lyro from your site and files.</p></div>
                    @if ($this->dataSources->isNotEmpty())
                        <span class="pill ok">Done</span>
                    @else
                        <button type="button" class="btn pri" wire:click="setTab('data-sources')">Start</button>
                    @endif
                </div>

                <div class="step">
                    <span class="n">2</span>
                    <div><b>Test in the playground</b><p>Ask a few questions before going live.</p></div>
                    <button type="button" class="btn pri" wire:click="setTab('playground')">Start</button>
                </div>

                <div class="step">
                    <span class="n">3</span>
                    <div><b>Choose channels</b><p>Turn on live chat and email.</p></div>
                    <button type="button" class="btn pri" wire:click="setTab('channels')">Start</button>
                </div>

                <div class="step">
                    <span class="n">4</span>
                    <div><b>Go live</b><p>Let Lyro answer real customers.</p></div>
                    <button type="button" class="btn pri" wire:click="setTab('channels')">Start</button>
                </div>
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
                    <select class="f" wire:model="audience_answer_for">
                        <option value="everyone">Everyone</option>
                        <option value="logged_in">Logged-in customers</option>
                        <option value="specific_countries">Specific countries</option>
                    </select>

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
                <p><b>Website</b><br><small>Crawl your site URLs</small></p>
            </div>
            <div class="card" style="cursor:pointer" wire:click="startAdd('pdf')">
                <i class="bi bi-file-earmark-text"></i>
                <p><b>Files</b><br><small>PDF, DOCX, TXT</small></p>
            </div>
            <div class="card" style="cursor:pointer" wire:click="startAdd('faq')">
                <i class="bi bi-question-circle"></i>
                <p><b>Q&amp;A</b><br><small>Write answers by hand</small></p>
            </div>
        </div>

        @if ($showAddForm)
            <div class="card" style="max-width:480px">
                <label style="margin-top:0">
                    {{ match($addingType) { 'url' => 'Page URL', 'pdf' => 'File name or path', 'faq' => 'Question' } }}
                </label>
                <input class="f" wire:model="new_source" placeholder="{{ match($addingType) { 'url' => 'https://yoursite.com/pricing', 'pdf' => 'refund-policy.pdf', 'faq' => 'How do I reset my password?' } }}">
                @error('new_source') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                <div style="margin-top:14px;display:flex;gap:8px">
                    <button type="button" class="btn pri" wire:click="addSource">Add source</button>
                    <button type="button" class="btn" wire:click="cancelAdd">Cancel</button>
                </div>
            </div>
        @endif

        <div class="card" style="padding:0">
            <table>
                <tr><th>Source</th><th>Type</th><th>Status</th><th>Added</th><th></th></tr>
                @forelse ($this->dataSources as $source)
                    <tr wire:key="source-{{ $source->id }}">
                        <td>{{ $source->source }}</td>
                        <td>{{ match($source->type->value) { 'url' => 'Website', 'pdf' => 'File', 'faq' => 'Q&A', 'help_center' => 'Help center' } }}</td>
                        <td>
                            <span class="pill {{ $source->status->value === 'synced' ? 'ok' : '' }}">
                                {{ match($source->status->value) { 'synced' => 'Ready', 'syncing' => 'Syncing', 'failed' => 'Failed', default => 'Processing' } }}
                            </span>
                        </td>
                        <td>{{ $source->created_at->format('M j') }}</td>
                        <td><button type="button" class="ib" wire:click="deleteSource({{ $source->id }})" aria-label="Remove source"><i class="bi bi-trash"></i></button></td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="color:var(--soft)">No data sources yet — add one above.</td></tr>
                @endforelse
            </table>
        </div>
        @endif
    </div>
</div>
