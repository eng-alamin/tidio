<?php

namespace App\Livewire\SuperAdmin;

use App\Enums\SuperAdminRole;
use App\Models\Role;
use App\Models\SuperAdmin;
use App\Models\User;
use App\Models\Workspace;
use App\Services\SuperAdmin\ImpersonationService;
use App\Services\SuperAdmin\UserService;
use App\Support\Impersonation;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;
use Throwable;

#[Layout('layouts.super-admin')]
#[Title('Users')]
class Users extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'role', except: '')]
    public string $roleFilter = '';

    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    /** null | form | delete | impersonate */
    public ?string $modal = null;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public ?int $workspaceId = null;

    public ?int $roleId = null;

    public ?int $impersonateWorkspaceId = null;

    // ---------------------------------------------------------------- data

    /** @return Collection<int, string> distinct role names used across all workspaces */
    #[Computed]
    public function roleNames(): Collection
    {
        return Role::query()->distinct()->orderBy('name')->pluck('name');
    }

    #[Computed]
    public function workspaces(): Collection
    {
        return Workspace::query()->orderBy('name')->get(['id', 'name']);
    }

    /** Roles of the workspace picked in the create form. */
    #[Computed]
    public function formRoles(): Collection
    {
        if (! $this->workspaceId) {
            return collect();
        }

        return Role::query()->where('workspace_id', $this->workspaceId)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function users(): LengthAwarePaginator
    {
        $search = trim($this->search);

        return User::query()
            ->with(['workspaces:id,name'])
            ->withCount('ownedWorkspaces as owned_count')
            // "Last active" = newest live session row. Only sessions that still exist in the
            // `sessions` table count (database session driver), so older activity shows as "—".
            ->addSelect([
                'last_active_at' => DB::table('sessions')
                    ->selectRaw('MAX(last_activity)')
                    ->whereColumn('sessions.user_id', 'users.id'),
            ])
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(fn ($q) => $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhereHas('workspaces', fn ($w) => $w->where('workspaces.name', 'like', $like)));
            })
            ->when($this->roleFilter !== '', fn ($q) => $q->whereHas('workspaces', fn ($w) => $w->whereIn(
                'workspace_user.role_id',
                fn ($sub) => $sub->select('id')->from('roles')->where('name', $this->roleFilter)
            )))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_disabled', false))
            ->when($this->statusFilter === 'disabled', fn ($q) => $q->where('is_disabled', true))
            ->latest()
            ->paginate(10);
    }

    /**
     * role_id => role name for every membership on the current page (one query, no N+1).
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function roleLabels(): Collection
    {
        $ids = $this->users->getCollection()
            ->flatMap(fn (User $u) => $u->workspaces->pluck('pivot.role_id'))
            ->filter()
            ->unique();

        return Role::query()->whereIn('id', $ids)->pluck('name', 'id');
    }

    public function lastActive(User $user): ?Carbon
    {
        return $user->last_active_at ? Carbon::createFromTimestamp((int) $user->last_active_at) : null;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedWorkspaceId(): void
    {
        // Roles are per workspace — pre-select the first one of the new workspace.
        unset($this->formRoles);
        $this->roleId = $this->formRoles->first()?->id;
    }

    /** Workspaces the open user can be impersonated in: active membership, workspace not suspended. */
    #[Computed]
    public function impersonationWorkspaces(): Collection
    {
        $user = $this->editingUser;

        if (! $user || $user->is_disabled) {
            return collect();
        }

        return $user->workspaces()
            ->wherePivot('status', 'active')
            ->where('workspaces.is_suspended', false)
            ->orderBy('workspaces.name')
            ->get(['workspaces.id', 'workspaces.name']);
    }

    // ---------------------------------------------------------- permissions

    public function canManage(): bool
    {
        return $this->admin()->role === SuperAdminRole::SuperAdmin;
    }

    public function canImpersonate(): bool
    {
        return ImpersonationService::canImpersonate($this->admin());
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
        $this->workspaceId = $this->workspaces->first()?->id;
        unset($this->formRoles);
        $this->roleId = $this->formRoles->first()?->id;
        $this->modal = 'form';
    }

    public function openEdit(int $id): void
    {
        abort_unless($this->canManage(), 403);

        $user = User::findOrFail($id);

        $this->resetForm();
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->modal = 'form';
    }

    public function confirmDelete(int $id): void
    {
        abort_unless($this->canManage(), 403);

        $this->resetForm();
        $this->editingId = User::findOrFail($id)->id;
        $this->modal = 'delete';
    }

    public function confirmImpersonate(int $id): void
    {
        abort_unless($this->canImpersonate(), 403);

        $this->resetForm();
        $this->editingId = User::findOrFail($id)->id;
        $this->impersonateWorkspaceId = $this->impersonationWorkspaces->first()?->id;
        $this->modal = 'impersonate';
    }

    public function closeModal(): void
    {
        $this->modal = null;
        $this->resetForm();
    }

    #[Computed]
    public function editingUser(): ?User
    {
        return $this->editingId ? User::find($this->editingId) : null;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'password', 'workspaceId', 'roleId', 'impersonateWorkspaceId']);
        $this->resetValidation();
        unset($this->editingUser, $this->formRoles, $this->impersonationWorkspaces);
    }

    // ------------------------------------------------------------- actions

    public function save(UserService $service): void
    {
        abort_unless($this->canManage(), 403);

        $isEdit = $this->editingId !== null;

        if ($isEdit) {
            $user = User::findOrFail($this->editingId);

            $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                'password' => ['nullable', 'string', 'min:8', 'max:255'],
            ]);

            try {
                $service->update($this->admin(), $user, [
                    'name' => $this->name,
                    'email' => $this->email,
                    'password' => $this->password !== '' ? $this->password : null,
                ]);
            } catch (Throwable $e) {
                report($e);
                $this->dispatch('toast', message: 'Could not update the user. Please try again.', type: 'error');

                return;
            }

            $this->dispatch('toast', message: "{$this->name} updated.", type: 'ok');
        } else {
            $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
                'password' => ['required', 'string', 'min:8', 'max:255'],
                'workspaceId' => ['required', 'integer', Rule::exists('workspaces', 'id')->whereNull('deleted_at')],
                'roleId' => ['required', 'integer', Rule::exists('roles', 'id')->where('workspace_id', $this->workspaceId)],
            ]);

            try {
                $service->create($this->admin(), [
                    'name' => $this->name,
                    'email' => $this->email,
                    'password' => $this->password,
                    'workspace_id' => (int) $this->workspaceId,
                    'role_id' => (int) $this->roleId,
                ]);
            } catch (Throwable $e) {
                report($e);
                $this->dispatch('toast', message: 'Could not create the user. Please try again.', type: 'error');

                return;
            }

            $this->resetPage();
            $this->dispatch('toast', message: "{$this->name} created.", type: 'ok');
        }

        $this->closeModal();
    }

    public function toggleDisabled(int $id, UserService $service): void
    {
        abort_unless($this->canManage(), 403);

        $user = User::findOrFail($id);
        $disable = ! $user->is_disabled;

        try {
            $service->setDisabled($this->admin(), $user, $disable);
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not change the user status.', type: 'error');

            return;
        }

        $this->dispatch(
            'toast',
            message: $disable ? "{$user->name} disabled." : "{$user->name} enabled.",
            type: $disable ? 'warn' : 'ok'
        );
    }

    public function delete(UserService $service): void
    {
        abort_unless($this->canManage(), 403);

        $user = User::findOrFail($this->editingId);

        try {
            $service->delete($this->admin(), $user);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: "{$user->name} can't be deleted. {$e->getMessage()}", type: 'error');
            $this->closeModal();

            return;
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not delete the user.', type: 'error');

            return;
        }

        $this->dispatch('toast', message: "{$user->name} deleted.", type: 'warn');
        $this->closeModal();
    }

    public function startImpersonation(ImpersonationService $service): void
    {
        abort_unless($this->canImpersonate(), 403);

        $user = User::findOrFail($this->editingId);

        // Only workspaces offered in the modal are accepted, never a raw id from the request.
        $workspace = $this->impersonationWorkspaces->firstWhere('id', $this->impersonateWorkspaceId);

        if (! $workspace) {
            $this->dispatch('toast', message: 'Choose a workspace this user can be impersonated in.', type: 'error');

            return;
        }

        $workspace = Workspace::findOrFail($workspace->id);

        try {
            $log = $service->start($this->admin(), $user, $workspace, request()->ip());
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');

            return;
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not start impersonation. Please try again.', type: 'error');

            return;
        }

        // Switching from another impersonation in this browser closes the old one first.
        if ($previous = Impersonation::data()) {
            $service->end($this->admin(), $previous['log_id'], 'switched');
        }

        Impersonation::begin($user, $workspace, $log, $this->admin()->id);

        $this->redirect(route('app.dashboard'));
    }

    public function render()
    {
        return view('livewire.super-admin.users');
    }
}
