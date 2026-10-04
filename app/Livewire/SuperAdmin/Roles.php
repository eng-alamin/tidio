<?php

namespace App\Livewire\SuperAdmin;

use App\Enums\SuperAdminRole;
use App\Models\SuperAdmin;
use App\Services\SuperAdmin\StaffService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;
use Throwable;

#[Layout('layouts.super-admin')]
#[Title('Roles & Permissions')]
class Roles extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'role', except: '')]
    public string $roleFilter = '';

    /** null | form | password | delete */
    public ?string $modal = null;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $role = 'support_staff';

    public string $password = '';

    public string $passwordConfirmation = '';

    public function mount(): void
    {
        // Staff accounts are the keys to the platform: super admins only, enforced on every request.
        abort_unless($this->canManage(), 403);

        if (! in_array($this->roleFilter, ['', ...array_column(SuperAdminRole::cases(), 'value')], true)) {
            $this->roleFilter = '';
        }
    }

    // ---------------------------------------------------------------- data

    /** @return array{total:int, super_admin:int, billing_admin:int, support_staff:int} */
    #[Computed]
    public function stats(): array
    {
        $counts = SuperAdmin::query()->selectRaw('role, count(*) as c')->groupBy('role')->pluck('c', 'role');

        return [
            'total' => (int) $counts->sum(),
            'super_admin' => (int) ($counts['super_admin'] ?? 0),
            'billing_admin' => (int) ($counts['billing_admin'] ?? 0),
            'support_staff' => (int) ($counts['support_staff'] ?? 0),
        ];
    }

    #[Computed]
    public function staff(): LengthAwarePaginator
    {
        $search = trim($this->search);

        return SuperAdmin::query()
            ->select(['id', 'name', 'email', 'role', 'created_at'])
            ->when($search !== '', fn (Builder $q) => $q->where(function (Builder $w) use ($search) {
                $w->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%');
            }))
            ->when($this->roleFilter !== '', fn (Builder $q) => $q->where('role', $this->roleFilter))
            ->orderBy('name')
            ->paginate(10);
    }

    #[Computed]
    public function editing(): ?SuperAdmin
    {
        return $this->editingId ? SuperAdmin::query()->select(['id', 'name', 'email', 'role'])->find($this->editingId) : null;
    }

    /**
     * Read-only summary of what each role can do. The real checks live in each page and service;
     * update this table whenever those rules change.
     *
     * @return array<int, array{area:string, super_admin:bool, billing_admin:bool, support_staff:bool}>
     */
    public function matrix(): array
    {
        return [
            ['area' => 'View every page and the audit log', 'super_admin' => true, 'billing_admin' => true, 'support_staff' => true],
            ['area' => 'Tenants: create, suspend, delete', 'super_admin' => true, 'billing_admin' => false, 'support_staff' => false],
            ['area' => 'Tenants: change plan', 'super_admin' => true, 'billing_admin' => true, 'support_staff' => false],
            ['area' => 'Users: create, edit, disable, delete', 'super_admin' => true, 'billing_admin' => false, 'support_staff' => false],
            ['area' => 'Users: impersonate', 'super_admin' => true, 'billing_admin' => false, 'support_staff' => true],
            ['area' => 'Invoices and coupons: manage', 'super_admin' => true, 'billing_admin' => true, 'support_staff' => false],
            ['area' => 'Coupons: delete', 'super_admin' => true, 'billing_admin' => false, 'support_staff' => false],
            ['area' => 'Feature flags, API keys, webhooks, integrations: change', 'super_admin' => true, 'billing_admin' => false, 'support_staff' => false],
            ['area' => 'Staff accounts and roles', 'super_admin' => true, 'billing_admin' => false, 'support_staff' => false],
            ['area' => 'See IP addresses in the audit log', 'super_admin' => true, 'billing_admin' => false, 'support_staff' => false],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    // ------------------------------------------------------------ presenters

    /** @return array{label:string, class:string} */
    public function roleBadge(SuperAdminRole $role): array
    {
        return match ($role) {
            SuperAdminRole::SuperAdmin => ['label' => 'Super Admin', 'class' => 'role-super'],
            SuperAdminRole::BillingAdmin => ['label' => 'Billing Admin', 'class' => 'role-billing'],
            SuperAdminRole::SupportStaff => ['label' => 'Support Staff', 'class' => 'role-support'],
        };
    }

    public function isSelf(int $id): bool
    {
        return $this->admin()->id === $id;
    }

    // ---------------------------------------------------------- permissions

    public function canManage(): bool
    {
        return $this->admin()->role === SuperAdminRole::SuperAdmin;
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
        $this->modal = 'form';
    }

    public function openEdit(int $id): void
    {
        abort_unless($this->canManage(), 403);

        $staff = SuperAdmin::findOrFail($id);

        $this->resetForm();
        $this->editingId = $staff->id;
        $this->name = $staff->name;
        $this->email = $staff->email;
        $this->role = $staff->role->value;
        $this->modal = 'form';
    }

    public function openPassword(int $id): void
    {
        abort_unless($this->canManage(), 403);

        $this->resetForm();
        $this->editingId = SuperAdmin::findOrFail($id)->id;
        $this->modal = 'password';
    }

    public function confirmDelete(int $id): void
    {
        abort_unless($this->canManage(), 403);

        $this->resetForm();
        $this->editingId = SuperAdmin::findOrFail($id)->id;
        $this->modal = 'delete';
    }

    public function closeModal(): void
    {
        $this->modal = null;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'password', 'passwordConfirmation']);
        $this->role = 'support_staff';
        $this->resetValidation();
        unset($this->editing);
    }

    // ------------------------------------------------------------- actions

    public function save(StaffService $service): void
    {
        abort_unless($this->canManage(), 403);
        abort_unless($this->modal === 'form', 422);

        $staff = $this->editingId !== null ? SuperAdmin::findOrFail($this->editingId) : null;

        $this->name = trim($this->name);
        $this->email = mb_strtolower(trim($this->email));

        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('super_admins', 'email')->ignore($staff?->id)],
            'role' => ['required', Rule::enum(SuperAdminRole::class)],
        ];

        if (! $staff) {
            $rules['password'] = ['required', 'string', Password::min(12)->mixedCase()->numbers()];
            $rules['passwordConfirmation'] = ['required', 'same:password'];
        }

        $this->validate($rules, [
            'email.unique' => 'Another staff account already uses this email.',
            'passwordConfirmation.same' => 'The two passwords don\'t match.',
            'passwordConfirmation.required' => 'Type the password again to confirm it.',
        ]);

        try {
            if ($staff) {
                $service->update($this->admin(), $staff->id, ['name' => $this->name, 'email' => $this->email, 'role' => $this->role]);
            } else {
                $service->create($this->admin(), ['name' => $this->name, 'email' => $this->email, 'role' => $this->role, 'password' => $this->password]);
            }
        } catch (UniqueConstraintViolationException) {
            $this->addError('email', 'Another staff account already uses this email.');

            return;
        } catch (RuntimeException $e) {
            $this->addError('role', $e->getMessage());

            return;
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not save the account. Please try again.', type: 'error');

            return;
        }

        if (! $staff) {
            $this->resetPage();
        }

        $this->dispatch('toast', message: $staff ? "{$this->name} updated." : "{$this->name} added. Share the password with them securely.", type: 'ok');
        $this->closeModal();
    }

    public function resetPassword(StaffService $service): void
    {
        abort_unless($this->canManage(), 403);
        abort_unless($this->modal === 'password' && $this->editingId !== null, 422);

        $this->validate([
            'password' => ['required', 'string', Password::min(12)->mixedCase()->numbers()],
            'passwordConfirmation' => ['required', 'same:password'],
        ], [
            'passwordConfirmation.same' => 'The two passwords don\'t match.',
            'passwordConfirmation.required' => 'Type the password again to confirm it.',
        ]);

        try {
            $staff = $service->resetPassword($this->admin(), $this->editingId, $this->password);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
            $this->closeModal();

            return;
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not reset the password.', type: 'error');

            return;
        }

        $this->dispatch('toast', message: "{$staff->name}'s password was reset. Share it with them securely.", type: 'warn');
        $this->closeModal();
    }

    public function delete(StaffService $service): void
    {
        abort_unless($this->canManage(), 403);
        abort_unless($this->modal === 'delete' && $this->editingId !== null, 422);

        try {
            $staff = $service->delete($this->admin(), $this->editingId);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
            $this->closeModal();

            return;
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not delete the account.', type: 'error');

            return;
        }

        $this->dispatch('toast', message: "{$staff->name} was removed.", type: 'warn');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.super-admin.roles');
    }
}
