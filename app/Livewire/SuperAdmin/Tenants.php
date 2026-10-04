<?php

namespace App\Livewire\SuperAdmin;

use App\Enums\SubscriptionStatus;
use App\Enums\SuperAdminRole;
use App\Models\Plan;
use App\Models\SuperAdmin;
use App\Models\Workspace;
use App\Services\SuperAdmin\TenantService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.super-admin')]
#[Title('Tenants')]
class Tenants extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    #[Url(as: 'plan', except: '')]
    public string $planFilter = '';

    /** null | form | suspend | delete */
    public ?string $modal = null;

    public ?int $editingId = null;

    public string $name = '';

    public string $ownerName = '';

    public string $ownerEmail = '';

    public string $ownerPassword = '';

    public ?int $planId = null;

    public string $suspendReason = '';

    public function mount(): void
    {
        if (request()->boolean('create') && $this->canManage()) {
            $this->openCreate();
        }
    }

    // ---------------------------------------------------------------- data

    #[Computed]
    public function plans(): Collection
    {
        return Plan::query()->orderBy('sort_order')->get(['id', 'name', 'slug']);
    }

    #[Computed]
    public function tenants(): LengthAwarePaginator
    {
        $search = trim($this->search);

        return Workspace::query()
            ->with(['owner:id,name,email', 'activeSubscription.plan'])
            ->withCount(['users as agents_count' => fn ($q) => $q->where('workspace_user.status', 'active')])
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('slug', 'like', $like)
                        ->orWhereHas('owner', fn ($o) => $o->where('name', 'like', $like)->orWhere('email', 'like', $like));
                });
            })
            ->when($this->planFilter !== '', fn ($q) => $q->where('plan', $this->planFilter))
            ->when($this->statusFilter !== '', fn ($q) => $this->applyStatusFilter($q, $this->statusFilter))
            ->latest()
            ->paginate(10);
    }

    private function applyStatusFilter($query, string $status): void
    {
        match ($status) {
            'suspended' => $query->where('is_suspended', true),
            'active' => $query->where('is_suspended', false)
                ->whereHas('activeSubscription', fn ($s) => $s->where('status', SubscriptionStatus::Active->value)),
            'past_due' => $query->where('is_suspended', false)
                ->whereHas('activeSubscription', fn ($s) => $s->where('status', SubscriptionStatus::PastDue->value)),
            'cancelled' => $query->where('is_suspended', false)
                ->whereHas('activeSubscription', fn ($s) => $s->where('status', SubscriptionStatus::Cancelled->value)),
            'trial' => $query->where('is_suspended', false)
                ->where(function ($q) {
                    $q->whereDoesntHave('activeSubscription')
                        ->orWhereHas('activeSubscription', fn ($s) => $s->where('status', SubscriptionStatus::Trialing->value));
                }),
            default => null,
        };
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPlanFilter(): void
    {
        $this->resetPage();
    }

    // ---------------------------------------------------------- permissions

    public function canManage(): bool
    {
        return $this->admin()->role === SuperAdminRole::SuperAdmin;
    }

    public function canChangePlan(): bool
    {
        return in_array($this->admin()->role, [SuperAdminRole::SuperAdmin, SuperAdminRole::BillingAdmin], true);
    }

    private function admin(): SuperAdmin
    {
        /** @var SuperAdmin */
        return Auth::guard('super_admin')->user();
    }

    // -------------------------------------------------------------- modals

    public function openCreate(): void
    {
        abort_unless($this->canManage(), 403);

        $this->resetForm();
        $this->planId = $this->plans->first()?->id;
        $this->modal = 'form';
    }

    public function openEdit(int $id): void
    {
        abort_unless($this->canManage() || $this->canChangePlan(), 403);

        $workspace = Workspace::with('owner:id,name,email')->findOrFail($id);

        $this->resetForm();
        $this->editingId = $workspace->id;
        $this->name = $workspace->name;
        $this->ownerName = $workspace->owner?->name ?? '';
        $this->ownerEmail = $workspace->owner?->email ?? '';
        $this->planId = $this->plans->firstWhere('slug', $workspace->plan)?->id ?? $this->plans->first()?->id;
        $this->modal = 'form';
    }

    public function confirmSuspend(int $id): void
    {
        abort_unless($this->canManage(), 403);

        $this->resetForm();
        $this->editingId = Workspace::findOrFail($id)->id;
        $this->modal = 'suspend';
    }

    public function confirmDelete(int $id): void
    {
        abort_unless($this->canManage(), 403);

        $this->resetForm();
        $this->editingId = Workspace::findOrFail($id)->id;
        $this->modal = 'delete';
    }

    public function closeModal(): void
    {
        $this->modal = null;
        $this->resetForm();
    }

    #[Computed]
    public function editingTenant(): ?Workspace
    {
        return $this->editingId ? Workspace::find($this->editingId) : null;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'ownerName', 'ownerEmail', 'ownerPassword', 'planId', 'suspendReason']);
        $this->resetValidation();
        unset($this->editingTenant);
    }

    // ------------------------------------------------------------- actions

    public function save(TenantService $service): void
    {
        $isEdit = $this->editingId !== null;

        // Editing: super admins change name + plan, billing admins plan only.
        abort_unless($isEdit ? ($this->canManage() || $this->canChangePlan()) : $this->canManage(), 403);

        if ($isEdit) {
            $workspace = Workspace::findOrFail($this->editingId);

            // Billing admins may only switch the plan; the name stays as it is.
            $name = $this->canManage() ? $this->name : $workspace->name;

            $this->validate([
                'name' => $this->canManage() ? ['required', 'string', 'max:255'] : ['nullable'],
                'planId' => ['required', 'integer', Rule::exists('plans', 'id')],
            ]);

            try {
                $service->update($this->admin(), $workspace, $name, (int) $this->planId);
            } catch (Throwable $e) {
                report($e);
                $this->dispatch('toast', message: 'Could not update the tenant. Please try again.', type: 'error');

                return;
            }

            $this->dispatch('toast', message: "{$name} updated.", type: 'ok');
        } else {
            $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'ownerName' => ['required', 'string', 'max:255'],
                'ownerEmail' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
                'ownerPassword' => ['required', 'string', 'min:8', 'max:255'],
                'planId' => ['required', 'integer', Rule::exists('plans', 'id')],
            ]);

            try {
                $service->create($this->admin(), [
                    'name' => $this->name,
                    'owner_name' => $this->ownerName,
                    'owner_email' => $this->ownerEmail,
                    'owner_password' => $this->ownerPassword,
                    'plan_id' => (int) $this->planId,
                ]);
            } catch (Throwable $e) {
                report($e);
                $this->dispatch('toast', message: 'Could not create the tenant. Please try again.', type: 'error');

                return;
            }

            $this->resetPage();
            $this->dispatch('toast', message: "{$this->name} created.", type: 'ok');
        }

        $this->closeModal();
    }

    public function suspend(TenantService $service): void
    {
        abort_unless($this->canManage(), 403);

        $this->validate(['suspendReason' => ['nullable', 'string', 'max:500']]);

        $workspace = Workspace::findOrFail($this->editingId);

        try {
            $service->suspend($this->admin(), $workspace, $this->suspendReason !== '' ? $this->suspendReason : null);
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not suspend the tenant.', type: 'error');

            return;
        }

        $this->dispatch('toast', message: "{$workspace->name} suspended.", type: 'warn');
        $this->closeModal();
    }

    public function unsuspend(int $id, TenantService $service): void
    {
        abort_unless($this->canManage(), 403);

        $workspace = Workspace::findOrFail($id);

        try {
            $service->unsuspend($this->admin(), $workspace);
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not reactivate the tenant.', type: 'error');

            return;
        }

        $this->dispatch('toast', message: "{$workspace->name} reactivated.", type: 'ok');
    }

    public function delete(TenantService $service): void
    {
        abort_unless($this->canManage(), 403);

        $workspace = Workspace::findOrFail($this->editingId);

        try {
            $service->delete($this->admin(), $workspace);
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not delete the tenant.', type: 'error');

            return;
        }

        $this->dispatch('toast', message: "{$workspace->name} deleted.", type: 'warn');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.super-admin.tenants');
    }
}
