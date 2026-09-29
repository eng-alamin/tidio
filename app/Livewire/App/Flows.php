<?php

namespace App\Livewire\App;

use App\Enums\FlowStatus;
use App\Models\Flow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Flows extends Component
{
    // welcome | flows | templates | strategies
    public string $tab = 'flows';

    // sales | leads | solve
    public string $category = 'sales';

    // My flows tab
    public string $search = '';

    public bool $showCreate = false;

    #[Validate('required|string|min:2|max:100')]
    public string $newName = '';

    #[Validate('required|string')]
    public string $newTrigger = 'page_visit';

    /**
     * Ready-made templates. "Use template" creates a Draft flow from one of these.
     * Trigger values must match FlowTriggerType.
     */
    public const TEMPLATES = [
        'sales' => [
            ['icon' => 'bi-cart', 'title' => 'Recover abandoned carts', 'desc' => 'Nudge visitors who are about to leave', 'trigger' => 'cart_abandonment'],
            ['icon' => 'bi-percent', 'title' => 'Offer a first-order discount', 'desc' => 'Welcome new visitors with a coupon', 'trigger' => 'page_visit'],
            ['icon' => 'bi-star', 'title' => 'Recommend a product', 'desc' => 'Ask a question, suggest the best fit', 'trigger' => 'time_on_page'],
        ],
        'leads' => [
            ['icon' => 'bi-envelope', 'title' => 'Collect emails', 'desc' => 'Ask for an email before chat starts', 'trigger' => 'page_visit'],
            ['icon' => 'bi-calendar-check', 'title' => 'Book a demo', 'desc' => 'Let visitors pick a time', 'trigger' => 'time_on_page'],
            ['icon' => 'bi-person-plus', 'title' => 'Newsletter signup', 'desc' => 'Invite readers to subscribe', 'trigger' => 'exit_intent'],
        ],
        'solve' => [
            ['icon' => 'bi-truck', 'title' => 'Order tracking', 'desc' => 'Answer where is my order', 'trigger' => 'manual'],
            ['icon' => 'bi-arrow-repeat', 'title' => 'Returns and refunds', 'desc' => 'Guide customers through a return', 'trigger' => 'manual'],
            ['icon' => 'bi-question-circle', 'title' => 'FAQ menu', 'desc' => 'Show common questions as buttons', 'trigger' => 'page_visit'],
        ],
    ];

    /**
     * Strategies = a bundle of flows created together.
     */
    public const STRATEGIES = [
        [
            'icon' => 'bi-bullseye',
            'title' => 'Turn visitors into customers',
            'desc' => 'A set of 3 flows for your homepage',
            'flows' => [
                ['name' => 'Homepage greeting', 'trigger' => 'page_visit'],
                ['name' => 'Product recommendation', 'trigger' => 'time_on_page'],
                ['name' => 'Exit offer', 'trigger' => 'exit_intent'],
            ],
        ],
        [
            'icon' => 'bi-heart',
            'title' => 'Keep customers happy',
            'desc' => 'A set of flows for support pages',
            'flows' => [
                ['name' => 'Support welcome', 'trigger' => 'page_visit'],
                ['name' => 'Need help? nudge', 'trigger' => 'time_on_page'],
            ],
        ],
        [
            'icon' => 'bi-lightning',
            'title' => 'Speed up support',
            'desc' => 'Automate the top 5 questions',
            'flows' => [
                ['name' => 'Order tracking', 'trigger' => 'manual'],
                ['name' => 'Returns and refunds', 'trigger' => 'manual'],
                ['name' => 'Shipping times', 'trigger' => 'manual'],
                ['name' => 'Payment methods', 'trigger' => 'manual'],
                ['name' => 'Contact a human', 'trigger' => 'manual'],
            ],
        ],
    ];

    public function mount(): void
    {
        // First-time visitors with no flows land on the Welcome tab.
        $hasFlows = Flow::where('workspace_id', app('currentWorkspace')->id)->exists();

        $this->tab = $hasFlows ? 'flows' : 'welcome';
    }

    #[Computed]
    public function flows(): Collection
    {
        return Flow::query()
            ->where('workspace_id', app('currentWorkspace')->id)
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->withCount(['runs as visitors_reached' => fn ($q) => $q->select(DB::raw('count(distinct visitor_id)'))])
            ->latest()
            ->get();
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['welcome', 'flows', 'templates', 'strategies'], true) ? $tab : 'flows';
    }

    public function openTemplates(string $category = 'sales'): void
    {
        $this->setCategory($category);
        $this->tab = 'templates';
    }

    public function setCategory(string $category): void
    {
        $this->category = array_key_exists($category, self::TEMPLATES) ? $category : 'sales';
    }

    // ---- My flows ----

    public function createFlow(): void
    {
        $this->validate();

        $flow = Flow::create([
            'workspace_id' => app('currentWorkspace')->id,
            'created_by' => auth()->id(),
            'name' => $this->newName,
            'trigger_type' => $this->newTrigger,
            'status' => FlowStatus::Draft,
        ]);

        $this->reset(['newName', 'showCreate']);
        $this->newTrigger = 'page_visit';
        unset($this->flows);

        $this->dispatch('toast', message: "Flow \"{$flow->name}\" created as a draft.");
    }

    public function cycleStatus(int $flowId): void
    {
        $flow = Flow::where('workspace_id', app('currentWorkspace')->id)->findOrFail($flowId);

        $flow->status = match ($flow->status) {
            FlowStatus::Draft => FlowStatus::Active,
            FlowStatus::Active => FlowStatus::Paused,
            FlowStatus::Paused => FlowStatus::Draft,
        };
        $flow->save();

        unset($this->flows);
    }

    public function deleteFlow(int $flowId): void
    {
        Flow::where('workspace_id', app('currentWorkspace')->id)->where('id', $flowId)->delete();
        unset($this->flows);

        $this->dispatch('toast', message: 'Flow deleted.');
    }

    // ---- Templates ----

    public function useTemplate(string $category, int $index): void
    {
        $template = self::TEMPLATES[$category][$index] ?? null;

        if (! $template) {
            return;
        }

        $this->makeFlow($template['title'], $template['trigger']);

        unset($this->flows);
        $this->tab = 'flows';

        $this->dispatch('toast', message: "\"{$template['title']}\" added to My flows as a draft.");
    }

    // ---- Strategies ----

    public function useStrategy(int $index): void
    {
        $strategy = self::STRATEGIES[$index] ?? null;

        if (! $strategy) {
            return;
        }

        DB::transaction(function () use ($strategy) {
            foreach ($strategy['flows'] as $flow) {
                $this->makeFlow($flow['name'], $flow['trigger']);
            }
        });

        unset($this->flows);
        $this->tab = 'flows';

        $count = count($strategy['flows']);
        $this->dispatch('toast', message: "{$count} draft flows added from \"{$strategy['title']}\".");
    }

    private function makeFlow(string $name, string $trigger): Flow
    {
        return Flow::create([
            'workspace_id' => app('currentWorkspace')->id,
            'created_by' => auth()->id(),
            'name' => $name,
            'trigger_type' => $trigger,
            'status' => FlowStatus::Draft,
        ]);
    }

    public function render()
    {
        return view('livewire.app.flows')
            ->layout('layouts.app', ['title' => 'Flows']);
    }
}