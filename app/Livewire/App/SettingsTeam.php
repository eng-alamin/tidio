<?php

namespace App\Livewire\App;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SettingsTeam extends Component
{
    public string $tab = 'operators';

    // Operators tab
    public bool $showInvite = false;

    #[Validate('required|email')]
    public string $inviteEmail = '';

    // Departments tab
    public bool $showCreateDept = false;

    public ?int $editingDeptId = null;

    #[Validate('required|string|max:100')]
    public string $deptName = '';

    // Roles tab
    public bool $showCreateRole = false;

    public ?int $editingRoleId = null;

    #[Validate('required|string|max:100')]
    public string $roleName = '';

    public bool $roleCanManageBilling = false;

    private const BILLING_PERMISSION = 'manage_billing';

    #[Computed]
    public function members(): Collection
    {
        $workspace = app('currentWorkspace');

        return $workspace->users()
            ->withPivot(['role_id', 'status'])
            ->get()
            ->map(function (User $user) use ($workspace) {
                $user->roleName = Role::find($user->pivot->role_id)?->name ?? 'Operator';

                return $user;
            });
    }

    #[Computed]
    public function departments(): Collection
    {
        return Department::query()
            ->where('workspace_id', app('currentWorkspace')->id)
            ->withCount('users')
            ->latest()
            ->get();
    }

    #[Computed]
    public function roles(): Collection
    {
        $workspace = app('currentWorkspace');

        return Role::query()
            ->where('workspace_id', $workspace->id)
            ->latest()
            ->get()
            ->map(function (Role $role) use ($workspace) {
                $role->membersCount = $workspace->users()->wherePivot('role_id', $role->id)->count();
                $role->canManageBilling = in_array(self::BILLING_PERMISSION, $role->permissions ?? [], true);

                return $role;
            });
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['operators', 'departments', 'roles'], true) ? $tab : 'operators';
    }

    // ---- Operators ----

    public function invite(): void
    {
        $this->validate(['inviteEmail' => 'required|email']);

        $workspace = app('currentWorkspace');

        $user = User::firstOrCreate(
            ['email' => $this->inviteEmail],
            ['name' => explode('@', $this->inviteEmail)[0], 'password' => bcrypt(str()->random(32))]
        );

        $alreadyMember = $workspace->users()->where('users.id', $user->id)->exists();

        if (! $alreadyMember) {
            $workspace->users()->attach($user->id, ['status' => 'invited']);
        }

        unset($this->members);
        $this->reset(['inviteEmail', 'showInvite']);

        $this->dispatch('toast', message: $alreadyMember
            ? 'That person is already on the team.'
            : "Invite sent to {$this->inviteEmail}.");
    }

    // ---- Departments ----

    public function startCreateDept(): void
    {
        $this->reset(['deptName', 'editingDeptId']);
        $this->showCreateDept = true;
    }

    public function editDept(int $deptId): void
    {
        $dept = Department::where('workspace_id', app('currentWorkspace')->id)->findOrFail($deptId);

        $this->editingDeptId = $dept->id;
        $this->deptName = $dept->name;
        $this->showCreateDept = true;
    }

    public function saveDept(): void
    {
        $this->validate(['deptName' => 'required|string|max:100']);

        if ($this->editingDeptId) {
            $dept = Department::where('workspace_id', app('currentWorkspace')->id)->findOrFail($this->editingDeptId);
            $dept->update(['name' => $this->deptName]);
            $toast = "Department \"{$this->deptName}\" updated.";
        } else {
            Department::create([
                'workspace_id' => app('currentWorkspace')->id,
                'name' => $this->deptName,
            ]);
            $toast = "Department \"{$this->deptName}\" created.";
        }

        $this->reset(['deptName', 'editingDeptId', 'showCreateDept']);
        unset($this->departments);

        $this->dispatch('toast', message: $toast);
    }

    public function cancelDept(): void
    {
        $this->reset(['deptName', 'editingDeptId', 'showCreateDept']);
    }

    public function deleteDept(int $deptId): void
    {
        Department::where('workspace_id', app('currentWorkspace')->id)->where('id', $deptId)->delete();
        unset($this->departments);

        $this->dispatch('toast', message: 'Department deleted.');
    }

    // ---- Roles ----

    public function startCreateRole(): void
    {
        $this->reset(['roleName', 'roleCanManageBilling', 'editingRoleId']);
        $this->showCreateRole = true;
    }

    public function editRole(int $roleId): void
    {
        $role = Role::where('workspace_id', app('currentWorkspace')->id)->findOrFail($roleId);

        $this->editingRoleId = $role->id;
        $this->roleName = $role->name;
        $this->roleCanManageBilling = in_array(self::BILLING_PERMISSION, $role->permissions ?? [], true);
        $this->showCreateRole = true;
    }

    public function saveRole(): void
    {
        $this->validate(['roleName' => 'required|string|max:100']);

        $permissions = $this->roleCanManageBilling ? [self::BILLING_PERMISSION] : [];

        if ($this->editingRoleId) {
            $role = Role::where('workspace_id', app('currentWorkspace')->id)->findOrFail($this->editingRoleId);
            $role->update([
                'name' => $this->roleName,
                'permissions' => $permissions,
            ]);
            $toast = "Role \"{$this->roleName}\" updated.";
        } else {
            Role::create([
                'workspace_id' => app('currentWorkspace')->id,
                'name' => $this->roleName,
                'permissions' => $permissions,
                'is_system' => false,
            ]);
            $toast = "Role \"{$this->roleName}\" created.";
        }

        $this->reset(['roleName', 'roleCanManageBilling', 'editingRoleId', 'showCreateRole']);
        unset($this->roles);

        $this->dispatch('toast', message: $toast);
    }

    public function cancelRole(): void
    {
        $this->reset(['roleName', 'roleCanManageBilling', 'editingRoleId', 'showCreateRole']);
    }

    public function deleteRole(int $roleId): void
    {
        $role = Role::where('workspace_id', app('currentWorkspace')->id)->where('id', $roleId)->first();

        if (! $role) {
            return;
        }

        if ($role->is_system) {
            $this->dispatch('toast', message: 'Built-in roles cannot be deleted.');
            return;
        }

        $role->delete();
        unset($this->roles);

        $this->dispatch('toast', message: 'Role deleted.');
    }

    public function render()
    {
        return view('livewire.app.settings-team')
            ->layout('layouts.app', ['title' => 'Settings']);
    }
}