<?php

namespace App\Livewire\App;

use App\Enums\AiDataSourceStatus;
use App\Enums\AiDataSourceType;
use App\Models\AiAgentSetting;
use App\Models\AiDataSource;
use App\Models\AiUnansweredQuestion;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Lyro extends Component
{
    /**
     * Which sub-tab is active. 'data-sources', 'setup', 'suggestions' and
     * 'guidance' are real, working tabs; the rest of Loop's Lyro AI Agent
     * section (Handoff, Actions, Procedures, Proactive roles, Playground,
     * Channels, Configure) is still the static prototype — those links stay
     * plain <a data-toast> until built, same as Settings did before each of
     * its pages was converted one at a time.
     */
    public string $tab = 'data-sources';

    // Guidance tab
    #[Validate('required|in:friendly,professional,playful')]
    public string $tone = 'friendly';

    #[Validate('nullable|string|max:2000')]
    public string $guidance_instructions = '';

    public bool $showAddForm = false;

    public ?string $addingType = null; // 'url' | 'pdf' | 'faq'

    public string $new_source = '';

    public ?int $answeringQuestionId = null;

    public string $answer_text = '';

    protected function rules(): array
    {
        return [
            'new_source' => ['required', 'string', 'max:255'],
            'addingType' => ['required', Rule::in(['url', 'pdf', 'faq'])],
        ];
    }

    public function mount(): void
    {
        $setting = app('currentWorkspace')->aiAgentSetting;
        $this->tone = $setting?->tone ?? 'friendly';
        $this->guidance_instructions = $setting?->guidance_instructions ?? '';
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['data-sources', 'setup', 'suggestions', 'guidance'], true) ? $tab : $this->tab;
    }

    public function saveGuidance(): void
    {
        $this->validate([
            'tone' => 'required|in:friendly,professional,playful',
            'guidance_instructions' => 'nullable|string|max:2000',
        ]);

        AiAgentSetting::updateOrCreate(
            ['workspace_id' => app('currentWorkspace')->id],
            ['tone' => $this->tone, 'guidance_instructions' => $this->guidance_instructions]
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
        $this->showAddForm = true;
        $this->resetValidation();
    }

    public function cancelAdd(): void
    {
        $this->showAddForm = false;
        $this->addingType = null;
        $this->new_source = '';
    }

    public function addSource(): void
    {
        $this->validate();

        AiDataSource::create([
            'workspace_id' => app('currentWorkspace')->id,
            'type' => AiDataSourceType::from($this->addingType),
            'source' => $this->new_source,
            // Real crawling/parsing/embedding isn't built — new sources land as
            // "pending" (matches the enum's default) rather than faking "Ready".
            'status' => AiDataSourceStatus::Pending,
        ]);

        unset($this->dataSources);
        $this->cancelAdd();

        $this->dispatch('toast', message: 'Data source added — pending processing.');
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
        return AiDataSource::where('workspace_id', app('currentWorkspace')->id)
            ->latest()
            ->get();
    }

    public function render()
    {
        return view('livewire.app.lyro')
            ->layout('layouts.app', ['title' => 'Lyro AI Agent']);
    }
}
