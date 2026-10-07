<?php

namespace App\Livewire\App;

use App\Enums\AiActionType;
use App\Enums\AiDataSourceStatus;
use App\Enums\AiDataSourceType;
use App\Jobs\SyncDataSource;
use App\Models\AiAction;
use App\Models\AiAgentSetting;
use App\Models\AiDataSource;
use App\Models\AiProactiveRole;
use App\Models\AiProcedure;
use App\Models\AiUnansweredQuestion;
use App\Services\Lyro\LyroSetupService;
use App\Services\LyroAiEngine;
use App\Support\Countries;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

class Lyro extends Component
{
    use WithFileUploads;

    /**
     * Which sub-tab is active. Opens on 'setup', the first tab, which shows the
     * live 4-step checklist (see LyroSetupService). Every tab is a real, working page.
     */
    public string $tab = 'setup';

    public bool $showRoleForm = false;

    public string $role_name = '';

    public string $role_goal = '';

    /** Sub-tab under Playground: 'live' | 'email'. */
    public string $playgroundChannel = 'live';

    /** In-memory only — a test run isn't a real Conversation, so nothing here is persisted. */
    public array $playgroundMessages = [];

    public string $playground_question = '';

    /** Sub-tab under Channels: 'live' | 'emails'. */
    public string $channelsTab = 'live';

    public bool $live_answer_enabled = true;

    public bool $live_outside_hours_only = false;

    public bool $email_answer_enabled = false;

    public bool $email_draft_only = true;

    /** Sub-tab under Configure: 'general' | 'audience' | 'copilot'. */
    public string $configureTab = 'general';

    public string $agent_name = 'Lyro';

    public string $default_language = 'auto';

    public string $audience_answer_for = 'everyone';

    public string $audience_exclude_tag = '';

    /** ISO alpha-2 codes Lyro answers for when audience_answer_for = "specific_countries". */
    public array $audience_countries = [];

    public bool $copilot_suggest_replies = true;

    public bool $copilot_summarize = true;

    public bool $showAddForm = false;

    public ?string $addingType = null; // 'url' | 'pdf' | 'faq'

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null  PDF chosen in the Files form */
    public $pdf_upload = null;

    public string $new_source = '';

    public ?int $answeringQuestionId = null;

    public string $answer_text = '';

    /** Sub-tab under Actions: 'actions' | 'mcps'. */
    public string $actionsTab = 'actions';

    public bool $showActionForm = false;

    public string $action_name = '';

    public string $action_type = 'webhook'; // 'webhook' | 'internal_lookup'

    public string $action_endpoint_url = '';

    public bool $showMcpForm = false;

    public string $mcp_name = '';

    public string $mcp_endpoint_url = '';

    public int $mcp_tools_count = 1;

    public bool $showProcedureForm = false;

    public string $procedure_title = '';

    public string $procedure_trigger = '';

    public string $procedure_steps = ''; // one step per line

    public string $tone = 'friendly';

    public string $instructions = '';

    public bool $handoff_on_request = true;

    public bool $handoff_on_low_confidence = true;

    public bool $handoff_on_negative_sentiment = false;

    public function mount(): void
    {
        $setting = AiAgentSetting::where('workspace_id', app('currentWorkspace')->id)->first();

        if ($setting) {
            $this->tone = $setting->tone;
            $this->agent_name = (string) ($setting->agent_name ?: 'Lyro');
            $this->default_language = (string) ($setting->default_language ?: 'auto');
            $this->audience_answer_for = (string) ($setting->audience_answer_for ?: 'everyone');
            $this->audience_exclude_tag = (string) $setting->audience_exclude_tag;
            $this->audience_countries = Countries::clean((array) ($setting->audience_countries ?? []));
            $this->copilot_suggest_replies = (bool) $setting->copilot_suggest_replies;
            $this->copilot_summarize = (bool) $setting->copilot_summarize;
            $this->instructions = (string) $setting->guidance_instructions;

            $rules = $setting->handoff_rules ?? [];
            $this->handoff_on_request = $rules['on_request'] ?? true;
            $this->handoff_on_low_confidence = $rules['on_low_confidence'] ?? true;
            $this->handoff_on_negative_sentiment = $rules['on_negative_sentiment'] ?? false;

            $channels = $setting->channel_rules ?? [];
            $this->live_answer_enabled = $channels['live_answer_enabled'] ?? true;
            $this->live_outside_hours_only = $channels['live_outside_hours_only'] ?? false;
            $this->email_answer_enabled = $channels['email_answer_enabled'] ?? false;
            $this->email_draft_only = $channels['email_draft_only'] ?? true;
        }
    }

    protected function rules(): array
    {
        $rules = ['addingType' => ['required', Rule::in(['url', 'pdf', 'faq'])]];

        if ($this->addingType === 'pdf') {
            // Files are uploaded (PDF only for now); a link to a PDF can be added as a Website source.
            $rules['pdf_upload'] = ['required', 'file', 'mimes:pdf', 'max:10240']; // 10 MB
        } elseif ($this->addingType === 'url') {
            $rules['new_source'] = ['required', 'string', 'max:255', 'url:http,https'];
        } else {
            $rules['new_source'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }

    // ---- Setup checklist (state is computed from the database — see LyroSetupService) ----

    /** @return array<string, mixed> steps, progress, blockers and warnings for the Setup tab. */
    #[Computed]
    public function setup(): array
    {
        return app(LyroSetupService::class)->overview(app('currentWorkspace'));
    }

    public function goLive(LyroSetupService $service): void
    {
        try {
            $service->goLive(app('currentWorkspace'));
        } catch (RuntimeException $e) {
            unset($this->setup);
            $this->dispatch('toast', message: $e->getMessage(), type: 'err');

            return;
        }

        unset($this->setup);
        $this->dispatch('toast', message: 'Lyro is live and will now answer customers.');
    }

    public function pauseLyro(LyroSetupService $service): void
    {
        $service->pause(app('currentWorkspace'));

        unset($this->setup);
        $this->dispatch('toast', message: 'Lyro paused. Customers will only hear from your team.');
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['data-sources', 'setup', 'suggestions', 'guidance', 'handoff', 'actions', 'procedures', 'proactive', 'playground', 'channels'], true) ? $tab : $this->tab;
    }

    public function setActionsTab(string $tab): void
    {
        $this->actionsTab = in_array($tab, ['actions', 'mcps'], true) ? $tab : 'actions';
    }

    // ---- Configure: General / Audience / Copilot ----

    public function setConfigureTab(string $tab): void
    {
        $this->configureTab = in_array($tab, ['general', 'audience', 'copilot'], true) ? $tab : 'general';
    }

    public function saveConfigureGeneral(): void
    {
        $this->validate([
            'agent_name' => ['required', 'string', 'max:60'],
            'default_language' => ['required', Rule::in(['auto', 'en', 'bn'])],
            'tone' => ['required', Rule::in(['friendly', 'professional', 'playful'])],
        ]);

        AiAgentSetting::updateOrCreate(
            ['workspace_id' => app('currentWorkspace')->id],
            ['agent_name' => trim($this->agent_name), 'default_language' => $this->default_language, 'tone' => $this->tone]
        );

        activity('lyro')->event('updated')->withProperties(['section' => 'general'])->log('Lyro general settings saved');

        $this->dispatch('toast', message: 'General settings saved.');
    }

    public function saveAudience(): void
    {
        $this->validate([
            'audience_answer_for' => ['required', Rule::in(['everyone', 'logged_in', 'specific_countries'])],
            'audience_exclude_tag' => ['nullable', 'string', 'max:60'],
            'audience_countries' => ['array', 'max:250'],
            'audience_countries.*' => ['string', Rule::in(array_keys(Countries::all()))],
        ]);

        $countries = Countries::clean($this->audience_countries);

        if ($this->audience_answer_for === 'specific_countries' && $countries === []) {
            $this->addError('audience_countries', 'Pick at least one country, or Lyro will not answer anyone.');

            return;
        }

        AiAgentSetting::updateOrCreate(
            ['workspace_id' => app('currentWorkspace')->id],
            [
                'audience_answer_for' => $this->audience_answer_for,
                'audience_exclude_tag' => trim($this->audience_exclude_tag) ?: null,
                'audience_countries' => $countries ?: null,
            ]
        );

        $this->audience_countries = $countries;

        activity('lyro')->event('updated')->withProperties(['section' => 'audience'])->log('Lyro audience saved');

        $this->dispatch('toast', message: 'Audience saved.');
    }

    public function updatedCopilotSuggestReplies(): void
    {
        $this->saveCopilot();
    }

    public function updatedCopilotSummarize(): void
    {
        $this->saveCopilot();
    }

    private function saveCopilot(): void
    {
        AiAgentSetting::updateOrCreate(
            ['workspace_id' => app('currentWorkspace')->id],
            ['copilot_suggest_replies' => $this->copilot_suggest_replies, 'copilot_summarize' => $this->copilot_summarize]
        );

        $this->dispatch('toast', message: 'Copilot settings updated.');
    }

    /** code => name, for the country picker. */
    #[Computed]
    public function countryOptions(): array
    {
        return Countries::all();
    }

    public function setChannelsTab(string $tab): void
    {
        $this->channelsTab = in_array($tab, ['live', 'emails'], true) ? $tab : 'live';
    }

    // ---- Channels — toggles save immediately, same pattern as Handoff ----

    public function updatedLiveAnswerEnabled(): void
    {
        $this->saveChannelRules();
    }

    public function updatedLiveOutsideHoursOnly(): void
    {
        $this->saveChannelRules();
    }

    public function updatedEmailAnswerEnabled(): void
    {
        $this->saveChannelRules();
    }

    public function updatedEmailDraftOnly(): void
    {
        $this->saveChannelRules();
    }

    private function saveChannelRules(): void
    {
        AiAgentSetting::updateOrCreate(
            ['workspace_id' => app('currentWorkspace')->id],
            [
                'tone' => $this->tone,
                'guidance_instructions' => $this->instructions,
                'channel_rules' => [
                    'live_answer_enabled' => $this->live_answer_enabled,
                    'live_outside_hours_only' => $this->live_outside_hours_only,
                    'email_answer_enabled' => $this->email_answer_enabled,
                    'email_draft_only' => $this->email_draft_only,
                ],
            ]
        );

        $this->dispatch('toast', message: 'Channel settings updated.');
    }

    #[Computed]
    public function procedures(): Collection
    {
        return AiProcedure::where('workspace_id', app('currentWorkspace')->id)
            ->orderBy('title')
            ->get();
    }

    /** Number of non-empty lines in a procedure's instructions — the "Steps" count shown in the table. */
    public function stepCount(AiProcedure $procedure): int
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $procedure->instructions))
            ->filter(fn ($line) => trim($line) !== '')
            ->count();
    }

    public function startAddProcedure(): void
    {
        $this->reset(['procedure_title', 'procedure_trigger', 'procedure_steps']);
        $this->showProcedureForm = true;
        $this->resetValidation();
    }

    public function cancelAddProcedure(): void
    {
        $this->showProcedureForm = false;
    }

    public function saveProcedure(): void
    {
        $this->validate([
            'procedure_title' => 'required|string|max:150',
            'procedure_trigger' => 'nullable|string|max:255',
            'procedure_steps' => 'required|string|max:4000',
        ]);

        AiProcedure::create([
            'workspace_id' => app('currentWorkspace')->id,
            'title' => $this->procedure_title,
            'trigger_condition' => $this->procedure_trigger ?: null,
            'instructions' => $this->procedure_steps,
            // Starts as a draft — same reasoning as new Actions: don't let Lyro
            // start following brand-new, untested steps with real customers.
            'is_active' => false,
        ]);

        unset($this->procedures);
        $this->showProcedureForm = false;

        $this->dispatch('toast', message: 'Procedure created as a draft.');
    }

    public function toggleProcedure(int $id): void
    {
        $procedure = AiProcedure::where('workspace_id', app('currentWorkspace')->id)->findOrFail($id);
        $procedure->is_active = ! $procedure->is_active;
        $procedure->save();

        unset($this->procedures);
    }

    public function deleteProcedure(int $id): void
    {
        AiProcedure::where('workspace_id', app('currentWorkspace')->id)->where('id', $id)->delete();
        unset($this->procedures);

        $this->dispatch('toast', message: 'Procedure deleted.');
    }

    #[Computed]
    public function actions(): Collection
    {
        return AiAction::where('workspace_id', app('currentWorkspace')->id)
            ->whereIn('type', [AiActionType::Webhook, AiActionType::InternalLookup])
            ->orderBy('name')
            ->get();
    }

    public function startAddAction(): void
    {
        $this->reset(['action_name', 'action_endpoint_url']);
        $this->action_type = 'webhook';
        $this->showActionForm = true;
        $this->resetValidation();
    }

    public function cancelAddAction(): void
    {
        $this->showActionForm = false;
    }

    public function saveAction(): void
    {
        $this->validate([
            'action_name' => 'required|string|max:100',
            'action_type' => [Rule::in(['webhook', 'internal_lookup'])],
            'action_endpoint_url' => $this->action_type === 'webhook' ? 'required|url|max:500' : 'nullable',
        ]);

        AiAction::create([
            'workspace_id' => app('currentWorkspace')->id,
            'name' => $this->action_name,
            'type' => AiActionType::from($this->action_type),
            'endpoint_url' => $this->action_type === 'webhook' ? $this->action_endpoint_url : null,
            // Off by default: a fresh action shouldn't start firing for real
            // customers before whoever built it has had a chance to test it.
            'is_active' => false,
        ]);

        unset($this->actions);
        $this->showActionForm = false;

        $this->dispatch('toast', message: 'Action created.');
    }

    public function toggleAction(int $id): void
    {
        $action = AiAction::where('workspace_id', app('currentWorkspace')->id)->findOrFail($id);
        $action->is_active = ! $action->is_active;
        $action->save();

        unset($this->actions);
    }

    public function deleteAction(int $id): void
    {
        AiAction::where('workspace_id', app('currentWorkspace')->id)->where('id', $id)->delete();
        unset($this->actions);

        $this->dispatch('toast', message: 'Action deleted.');
    }

    // ---- MCPs (Actions' second sub-tab — same ai_actions table, type=mcp) ----

    #[Computed]
    public function mcps(): Collection
    {
        return AiAction::where('workspace_id', app('currentWorkspace')->id)
            ->where('type', AiActionType::Mcp)
            ->orderBy('name')
            ->get();
    }

    public function startAddMcp(): void
    {
        $this->reset(['mcp_name', 'mcp_endpoint_url']);
        $this->mcp_tools_count = 1;
        $this->showMcpForm = true;
        $this->resetValidation();
    }

    public function cancelAddMcp(): void
    {
        $this->showMcpForm = false;
    }

    public function saveMcp(): void
    {
        $this->validate([
            'mcp_name' => 'required|string|max:100',
            'mcp_endpoint_url' => 'required|url|max:500',
            'mcp_tools_count' => 'required|integer|min:0|max:200',
        ]);

        AiAction::create([
            'workspace_id' => app('currentWorkspace')->id,
            'name' => $this->mcp_name,
            'type' => AiActionType::Mcp,
            'endpoint_url' => $this->mcp_endpoint_url,
            'config' => ['tools_count' => $this->mcp_tools_count],
            // Same reasoning as new Actions/Procedures: an unverified MCP
            // server shouldn't start fielding real customer requests.
            'is_active' => false,
        ]);

        unset($this->mcps);
        $this->showMcpForm = false;

        $this->dispatch('toast', message: "MCP server \"{$this->mcp_name}\" added — not connected yet.");
    }

    public function toggleMcp(int $id): void
    {
        $mcp = AiAction::where('workspace_id', app('currentWorkspace')->id)
            ->where('type', AiActionType::Mcp)
            ->findOrFail($id);

        $mcp->is_active = ! $mcp->is_active;
        $mcp->save();

        unset($this->mcps);
    }

    public function deleteMcp(int $id): void
    {
        AiAction::where('workspace_id', app('currentWorkspace')->id)
            ->where('type', AiActionType::Mcp)
            ->where('id', $id)
            ->delete();

        unset($this->mcps);

        $this->dispatch('toast', message: 'MCP server removed.');
    }

    // ---- Proactive roles ----

    #[Computed]
    public function proactiveRoles(): Collection
    {
        return AiProactiveRole::where('workspace_id', app('currentWorkspace')->id)
            ->orderBy('name')
            ->get();
    }

    public function startAddRole(): void
    {
        $this->reset(['role_name', 'role_goal']);
        $this->showRoleForm = true;
        $this->resetValidation();
    }

    public function cancelAddRole(): void
    {
        $this->showRoleForm = false;
    }

    public function saveRole(): void
    {
        $this->validate([
            'role_name' => 'required|string|max:100',
            'role_goal' => 'required|string|max:255',
        ]);

        AiProactiveRole::create([
            'workspace_id' => app('currentWorkspace')->id,
            'name' => $this->role_name,
            'goal' => $this->role_goal,
            // Off by default, same reasoning as Actions/Procedures/MCPs: don't
            // let a brand-new proactive role start messaging real visitors
            // before someone reviews it.
            'is_active' => false,
        ]);

        unset($this->proactiveRoles);
        $this->showRoleForm = false;

        $this->dispatch('toast', message: "Role \"{$this->role_name}\" created.");
    }

    public function toggleRole(int $id): void
    {
        $role = AiProactiveRole::where('workspace_id', app('currentWorkspace')->id)->findOrFail($id);
        $role->is_active = ! $role->is_active;
        $role->save();

        unset($this->proactiveRoles);
    }

    public function deleteRole(int $id): void
    {
        AiProactiveRole::where('workspace_id', app('currentWorkspace')->id)->where('id', $id)->delete();
        unset($this->proactiveRoles);

        $this->dispatch('toast', message: 'Role deleted.');
    }

    // ---- Playground ----
    // A sandbox to test how Lyro would reply — nothing here is saved as a
    // real Conversation. Replies come from LyroAiEngine (Anthropic's API),
    // using this workspace's real Guidance/tone settings and synced Data
    // sources as context. Requires ANTHROPIC_API_KEY to be set — without
    // it, LyroAiEngine itself returns a clear "not connected" message
    // rather than silently failing.

    public function setPlaygroundChannel(string $channel): void
    {
        $this->playgroundChannel = in_array($channel, ['live', 'email'], true) ? $channel : 'live';
        $this->playgroundMessages = [];
    }

    #[Computed]
    public function playgroundSources(): Collection
    {
        return AiDataSource::where('workspace_id', app('currentWorkspace')->id)
            ->where('status', AiDataSourceStatus::Synced)
            ->latest()
            ->limit(5)
            ->get(AiDataSource::LIST_COLUMNS);
    }

    public function sendTestMessage(LyroAiEngine $engine): void
    {
        $this->validate(['playground_question' => 'required|string|max:500'], attributes: ['playground_question' => 'question']);

        $question = trim($this->playground_question);
        $history = $this->playgroundMessages; // turns before this one, for conversational context

        $this->playgroundMessages[] = ['from' => 'me', 'text' => $question];
        $this->playground_question = '';

        $reply = $engine->reply(
            workspace: app('currentWorkspace'),
            question: $question,
            history: $history,
            channel: $this->playgroundChannel === 'email' ? 'email' : 'live chat',
        );

        $this->playgroundMessages[] = ['from' => 'bot', 'text' => $reply];

        // Only a real AI answer counts as "tested" for the Setup checklist — not the
        // "engine not connected" placeholder and not an API error message.
        if ($engine->isAvailable() && ! str_starts_with($reply, LyroAiEngine::REPLY_FAILED_PREFIX)) {
            app(LyroSetupService::class)->markPlaygroundTested(app('currentWorkspace'));
            unset($this->setup);
        }
    }

    public function clearPlayground(): void
    {
        $this->playgroundMessages = [];
    }

    #[Computed]
    public function aiEngineAvailable(): bool
    {
        return app(LyroAiEngine::class)->isAvailable();
    }

    // Handoff toggles save immediately on change (no Save button in the static
    // design), unlike Guidance's form which has an explicit Save button.
    public function updatedHandoffOnRequest(): void
    {
        $this->saveHandoffRules();
    }

    public function updatedHandoffOnLowConfidence(): void
    {
        $this->saveHandoffRules();
    }

    public function updatedHandoffOnNegativeSentiment(): void
    {
        $this->saveHandoffRules();
    }

    private function saveHandoffRules(): void
    {
        AiAgentSetting::updateOrCreate(
            ['workspace_id' => app('currentWorkspace')->id],
            [
                'tone' => $this->tone,
                'guidance_instructions' => $this->instructions,
                'handoff_rules' => [
                    'on_request' => $this->handoff_on_request,
                    'on_low_confidence' => $this->handoff_on_low_confidence,
                    'on_negative_sentiment' => $this->handoff_on_negative_sentiment,
                ],
            ]
        );

        $this->dispatch('toast', message: 'Handoff rules updated.');
    }

    public function saveGuidance(): void
    {
        $this->validate([
            'tone' => ['required', Rule::in(['friendly', 'professional', 'playful'])],
            'instructions' => 'nullable|string|max:2000',
        ]);

        AiAgentSetting::updateOrCreate(
            ['workspace_id' => app('currentWorkspace')->id],
            ['tone' => $this->tone, 'guidance_instructions' => $this->instructions]
        );

        $this->dispatch('toast', message: 'Guidance saved.');
    }

    public function startAnswer(int $questionId): void
    {
        $this->answeringQuestionId = $questionId;
        $this->answer_text = '';
        $this->resetValidation();
    }

    public function cancelAnswer(): void
    {
        $this->answeringQuestionId = null;
        $this->answer_text = '';
    }

    public function saveAnswer(): void
    {
        $this->validate(['answer_text' => 'required|string|max:2000']);

        $question = AiUnansweredQuestion::where('workspace_id', app('currentWorkspace')->id)
            ->whereNull('resolved_at')
            ->findOrFail($this->answeringQuestionId);

        // Turns the gap into knowledge: the Q&A becomes a real FAQ data source,
        // and the question is marked resolved so it drops off this list.
        AiDataSource::create([
            'workspace_id' => app('currentWorkspace')->id,
            'type' => AiDataSourceType::Faq,
            'source' => $question->question."\n".$this->answer_text,
            'status' => AiDataSourceStatus::Synced,
        ]);

        $question->update(['resolved_at' => now()]);

        unset($this->unansweredQuestions, $this->dataSources);
        $this->cancelAnswer();

        $this->dispatch('toast', message: 'Answer added to Lyro.');
    }

    public function dismissQuestion(int $questionId): void
    {
        AiUnansweredQuestion::where('workspace_id', app('currentWorkspace')->id)
            ->where('id', $questionId)
            ->update(['resolved_at' => now()]);

        unset($this->unansweredQuestions);
    }

    #[Computed]
    public function unansweredQuestions(): Collection
    {
        return AiUnansweredQuestion::where('workspace_id', app('currentWorkspace')->id)
            ->whereNull('resolved_at')
            ->orderByDesc('asked_count')
            ->get();
    }

    public function startAdd(string $type): void
    {
        $this->addingType = in_array($type, ['url', 'pdf', 'faq'], true) ? $type : 'url';
        $this->new_source = '';
        $this->pdf_upload = null;
        $this->showAddForm = true;
        $this->resetValidation();
    }

    public function cancelAdd(): void
    {
        $this->showAddForm = false;
        $this->addingType = null;
        $this->new_source = '';
        $this->pdf_upload = null;
    }

    public function addSource(): void
    {
        $this->validate();

        $workspaceId = app('currentWorkspace')->id;
        $type = AiDataSourceType::from($this->addingType);

        if ($type === AiDataSourceType::Pdf) {
            // Kept on the private disk (never publicly reachable); `source` is just the display name.
            $path = $this->pdf_upload->store("ai-sources/{$workspaceId}", 'local');

            $source = AiDataSource::create([
                'workspace_id' => $workspaceId,
                'type' => $type,
                'source' => mb_substr($this->pdf_upload->getClientOriginalName(), 0, 255),
                'file_path' => $path,
                'status' => AiDataSourceStatus::Pending,
            ]);
        } else {
            $address = trim($this->new_source);

            // Adding the same address twice just reads it again instead of making a duplicate.
            $source = $type === AiDataSourceType::Url
                ? AiDataSource::query()->where('workspace_id', $workspaceId)->where('type', $type->value)->where('source', $address)->first()
                : null;

            $source ??= AiDataSource::create([
                'workspace_id' => $workspaceId,
                'type' => $type,
                'source' => $address,
                'status' => AiDataSourceStatus::Pending,
            ]);
        }

        unset($this->dataSources);
        $this->cancelAdd();

        if ($type === AiDataSourceType::Faq) {
            $this->dispatch('toast', message: 'Data source added.');

            return;
        }

        $source->forceFill(['status' => AiDataSourceStatus::Pending, 'error' => null])->save();
        SyncDataSource::start($source->id); // reads the site / PDF right after this response

        $this->dispatch('toast', message: 'Data source added — Lyro is reading it now.');
    }

    public function resyncSource(int $id): void
    {
        $source = AiDataSource::where('workspace_id', app('currentWorkspace')->id)
            ->whereIn('type', [AiDataSourceType::Url->value, AiDataSourceType::Pdf->value, AiDataSourceType::HelpCenter->value])
            ->find($id);

        if (! $source || $source->status === AiDataSourceStatus::Syncing) {
            return;
        }

        $source->forceFill(['status' => AiDataSourceStatus::Pending, 'error' => null])->save();
        unset($this->dataSources);

        SyncDataSource::start($source->id);

        $this->dispatch('toast', message: 'Reading it again…');
    }

    public function deleteSource(int $id): void
    {
        AiDataSource::where('workspace_id', app('currentWorkspace')->id)->where('id', $id)->delete();
        unset($this->dataSources);

        $this->dispatch('toast', message: 'Data source removed.');
    }

    #[Computed]
    public function dataSources(): Collection
    {
        // Without `content` (can be hundreds of KB per source) — the list never needs it.
        return AiDataSource::where('workspace_id', app('currentWorkspace')->id)
            ->latest()
            ->get(AiDataSource::LIST_COLUMNS);
    }

    public function render()
    {
        return view('livewire.app.lyro')
            ->layout('layouts.app', ['title' => 'Lyro AI Agent']);
    }
}