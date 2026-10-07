<?php

namespace App\Livewire\SuperAdmin;

use App\Enums\TenantStatus;
use App\Models\Workspace;
use App\Services\SuperAdmin\OnboardingReportService;
use App\Support\OnboardingSteps;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.super-admin')]
#[Title('Onboarding')]
class Onboarding extends Component
{
    use WithPagination;

    private const STATUSES = ['complete', 'in_progress', 'not_started'];

    protected string $paginationTheme = 'bootstrap';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** complete | in_progress | not_started */
    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    public function mount(): void
    {
        if (! in_array($this->statusFilter, ['', ...self::STATUSES], true)) {
            $this->statusFilter = '';
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    // ---------------------------------------------------------------- data

    private function report(): OnboardingReportService
    {
        return app(OnboardingReportService::class);
    }

    #[Computed]
    public function totalTenants(): int
    {
        return Workspace::count();
    }

    /** @return array{complete:int, in_progress:int, not_started:int} */
    #[Computed]
    public function summary(): array
    {
        $counts = array_values($this->report()->progressCounts());
        $total = OnboardingSteps::total();
        $complete = count(array_filter($counts, fn (int $c) => $c >= $total));

        return [
            'complete' => $complete,
            'in_progress' => count($counts) - $complete,
            'not_started' => max(0, $this->totalTenants - count($counts)),
        ];
    }

    /** @return list<array{key:string, label:string, icon:string, color:string, count:int, percent:int}> */
    #[Computed]
    public function stepStats(): array
    {
        return $this->report()->stepStats($this->totalTenants);
    }

    #[Computed]
    public function tenants(): LengthAwarePaginator
    {
        $search = trim($this->search);
        $filter = $this->statusFilter !== '' ? $this->report()->idsForStatus($this->statusFilter) : null;

        return Workspace::query()
            ->with('activeSubscription')
            ->when($search !== '', fn (Builder $q) => $q->where('name', 'like', '%'.$search.'%'))
            ->when($filter !== null, fn (Builder $q) => $filter['exclude']
                ? $q->whereNotIn('id', $filter['ids'])
                : $q->whereIn('id', $filter['ids']))
            ->latest('id')
            ->paginate(10);
    }

    /** @return array<int, \Carbon\Carbon> */
    #[Computed]
    public function lastActivity(): array
    {
        return $this->report()->lastActivity($this->tenants->pluck('id')->all());
    }

    /** @return array<string, array<int, true>> */
    #[Computed]
    public function completed(): array
    {
        return $this->report()->completedSets();
    }

    // ------------------------------------------------------------ presenters

    /** @return array{done:int, label:string, class:string} */
    public function rowStatus(Workspace $tenant): array
    {
        $done = 0;
        foreach (OnboardingSteps::keys() as $key) {
            $done += isset($this->completed[$key][$tenant->id]) ? 1 : 0;
        }

        return match (true) {
            $done >= OnboardingSteps::total() => ['done' => $done, 'label' => 'Complete', 'class' => 'status-active'],
            $done > 0 => ['done' => $done, 'label' => 'In progress', 'class' => 'status-trial'],
            default => ['done' => $done, 'label' => 'Not started', 'class' => 'status-suspended'],
        };
    }

    public function tenantStatus(Workspace $tenant): TenantStatus
    {
        return TenantStatus::fromWorkspace($tenant);
    }

    public function render()
    {
        return view('livewire.super-admin.onboarding');
    }
}
