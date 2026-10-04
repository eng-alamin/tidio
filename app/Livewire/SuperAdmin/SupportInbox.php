<?php

namespace App\Livewire\SuperAdmin;

use App\Enums\LeadStatus;
use App\Enums\SuperAdminRole;
use App\Models\ContactSalesLead;
use App\Models\SuperAdmin;
use App\Services\SuperAdmin\LeadService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;
use Throwable;

#[Layout('layouts.super-admin')]
#[Title('Support Inbox')]
class SupportInbox extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    public ?int $viewingId = null;

    public function mount(): void
    {
        if ($this->statusFilter !== '' && LeadStatus::tryFrom($this->statusFilter) === null) {
            $this->statusFilter = '';
        }
    }

    // ---------------------------------------------------------------- data

    /** @return array<string, int> status value => count, plus 'total' */
    #[Computed]
    public function stats(): array
    {
        $counts = ContactSalesLead::query()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $out = ['total' => (int) $counts->sum()];
        foreach (LeadStatus::cases() as $case) {
            $out[$case->value] = (int) ($counts[$case->value] ?? 0);
        }

        return $out;
    }

    #[Computed]
    public function leads(): LengthAwarePaginator
    {
        $search = trim($this->search);

        return ContactSalesLead::query()
            ->when($search !== '', fn (Builder $q) => $q->where(function (Builder $w) use ($search) {
                $w->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('company', 'like', '%'.$search.'%');
            }))
            ->when($this->statusFilter !== '', fn (Builder $q) => $q->where('status', $this->statusFilter))
            ->latest('id')
            ->paginate(10);
    }

    #[Computed]
    public function viewing(): ?ContactSalesLead
    {
        return $this->viewingId ? ContactSalesLead::query()->find($this->viewingId) : null;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    // ------------------------------------------------------------ presenters

    /** @return array{label:string, class:string} */
    public function badge(LeadStatus $status): array
    {
        return match ($status) {
            LeadStatus::New => ['label' => 'New', 'class' => 'status-trial'],
            LeadStatus::Contacted => ['label' => 'Contacted', 'class' => 'status-active'],
            LeadStatus::Qualified => ['label' => 'Qualified', 'class' => 'status-active'],
            LeadStatus::Closed => ['label' => 'Closed', 'class' => 'status-suspended'],
        };
    }

    // ---------------------------------------------------------- permissions

    /** Support staff work the inbox; billing admins can read it. */
    public function canManage(): bool
    {
        return in_array($this->admin()->role, [SuperAdminRole::SuperAdmin, SuperAdminRole::SupportStaff], true);
    }

    private function admin(): SuperAdmin
    {
        /** @var SuperAdmin */
        return Auth::guard('super_admin')->user();
    }

    // -------------------------------------------------------------- modals

    public function open(int $id): void
    {
        $this->viewingId = ContactSalesLead::query()->findOrFail($id)->id;
        unset($this->viewing);
    }

    public function closeModal(): void
    {
        $this->viewingId = null;
        unset($this->viewing);
    }

    // ------------------------------------------------------------- actions

    public function setStatus(int $id, string $status, LeadService $service): void
    {
        abort_unless($this->canManage(), 403);

        $target = LeadStatus::tryFrom($status);
        abort_if($target === null, 422);

        try {
            $service->setStatus($this->admin(), $id, $target);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
            $this->closeModal();

            return;
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not update the request.', type: 'error');

            return;
        }

        unset($this->viewing, $this->stats);
        $this->dispatch('toast', message: 'Marked '.strtolower($this->badge($target)['label']).'.', type: 'ok');
    }

    public function render()
    {
        return view('livewire.super-admin.support-inbox');
    }
}
