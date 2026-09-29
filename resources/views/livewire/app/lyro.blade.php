<div class="body">
    <div class="content">
        <div class="subtabs">
            <a href="#" wire:click.prevent="setTab('setup')" class="{{ $tab === 'setup' ? 'on' : '' }}">Setup</a>
            <a href="#" wire:click.prevent="setTab('data-sources')" class="{{ $tab === 'data-sources' ? 'on' : '' }}">Data sources</a>
            <a href="#" wire:click.prevent="setTab('suggestions')" class="{{ $tab === 'suggestions' ? 'on' : '' }}">Suggestions</a>
            <a href="#" wire:click.prevent="setTab('guidance')" class="{{ $tab === 'guidance' ? 'on' : '' }}">Guidance</a>
            <a href="#" data-toast="Handoff — coming soon.">Handoff</a>
            <a href="#" data-toast="Actions — coming soon.">Actions</a>
            <a href="#" data-toast="Procedures — coming soon.">Procedures</a>
            <a href="#" data-toast="Proactive roles — coming soon.">Proactive roles</a>
            <a href="#" data-toast="Playground — coming soon.">Playground</a>
            <a href="#" data-toast="Channels — coming soon.">Channels</a>
            <a href="#" data-toast="Configure — coming soon.">Configure</a>
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
                    <button type="button" class="btn pri" data-toast="Playground — coming soon.">Start</button>
                </div>

                <div class="step">
                    <span class="n">3</span>
                    <div><b>Choose channels</b><p>Turn on live chat and email.</p></div>
                    <button type="button" class="btn pri" data-toast="Channels — coming soon.">Start</button>
                </div>

                <div class="step">
                    <span class="n">4</span>
                    <div><b>Go live</b><p>Let Lyro answer real customers.</p></div>
                    <button type="button" class="btn pri" data-toast="Channels — coming soon.">Start</button>
                </div>
            </div>
        @elseif ($tab === 'guidance')
            <h2 class="sec" style="margin-top:0">Guide how Lyro talks</h2>
            <div class="card" style="max-width:640px">
                <label style="margin-top:0">Tone of voice</label>
                <select class="f" wire:model="tone">
                    <option value="friendly">Friendly</option>
                    <option value="professional">Professional</option>
                    <option value="playful">Playful</option>
                </select>
                @error('tone') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                <label>Extra instructions</label>
                <textarea class="f" rows="5" wire:model="guidance_instructions" placeholder="Keep answers short. Never promise refund dates."></textarea>
                @error('guidance_instructions') <small style="color:var(--bad)">{{ $message }}</small> @enderror

                <br>
                <button type="button" class="btn pri" style="margin-top:18px" wire:click="saveGuidance">Save guidance</button>
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
