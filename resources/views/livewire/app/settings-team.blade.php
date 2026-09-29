<div class="body">
    @include('livewire.app.settings-nav')

    <div class="content">
        <h2 style="font-size:22px;margin-bottom:14px">Team</h2>
        <div class="subtabs">
            <a href="#" wire:click.prevent="setTab('operators')" class="{{ $tab === 'operators' ? 'on' : '' }}">Operators</a>
            <a href="#" wire:click.prevent="setTab('departments')" class="{{ $tab === 'departments' ? 'on' : '' }}">Departments</a>
            <a href="#" wire:click.prevent="setTab('roles')" class="{{ $tab === 'roles' ? 'on' : '' }}">Roles</a>
        </div>

        @if ($tab === 'operators')
            <div style="display:flex;justify-content:space-between;margin-bottom:14px;align-items:center">
                <span style="color:var(--soft)">Invite people to handle conversations.</span>
                <button class="btn pri" wire:click="$set('showInvite', true)">+ Invite operator</button>
            </div>

            @if ($showInvite)
                <div class="card" style="padding:16px;margin-bottom:16px">
                    <form wire:submit="invite" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
                        <div style="flex:1;min-width:220px">
                            <label style="display:block;font-size:12px;margin-bottom:4px">Email address</label>
                            <input class="f" wire:model="inviteEmail" placeholder="operator@example.com">
                            @error('inviteEmail') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                        </div>
                        <button type="submit" class="btn pri">Send invite</button>
                        <button type="button" class="btn" wire:click="$set('showInvite', false)">Cancel</button>
                    </form>
                </div>
            @endif

            <div class="card" style="padding:0">
                <table>
                    <tr><th>Name</th><th>Role</th><th>Status</th></tr>
                    @forelse ($this->members as $member)
                        <tr wire:key="member-{{ $member->id }}">
                            <td><span class="av">{{ strtoupper(substr($member->name, 0, 1)) }}</span>{{ $member->name }}</td>
                            <td>{{ $member->roleName }}</td>
                            <td>
                                <span class="pill {{ $member->pivot->status === 'active' ? 'ok' : '' }}">
                                    {{ ucfirst($member->pivot->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" style="color:var(--soft)">No team members yet.</td></tr>
                    @endforelse
                </table>
            </div>

        @elseif ($tab === 'departments')
            <div class="tools">
                <span style="flex:1"></span>
                <button class="btn pri" wire:click="startCreateDept">+ New department</button>
            </div>

            @if ($showCreateDept)
                <div class="card" style="padding:16px;margin-bottom:16px">
                    <form wire:submit="saveDept" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
                        <div style="flex:1;min-width:200px">
                            <label style="display:block;font-size:12px;margin-bottom:4px">Department name</label>
                            <input class="f" wire:model="deptName" placeholder="e.g. Support">
                            @error('deptName') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                        </div>
                        <button type="submit" class="btn pri">{{ $editingDeptId ? 'Save' : 'Create' }}</button>
                        <button type="button" class="btn" wire:click="cancelDept">Cancel</button>
                    </form>
                </div>
            @endif

            <div class="card" style="padding:0">
                <table>
                    <tr><th>Department</th><th>Operators</th><th></th></tr>
                    @forelse ($this->departments as $dept)
                        <tr wire:key="dept-{{ $dept->id }}">
                            <td>{{ $dept->name }}</td>
                            <td>{{ $dept->users_count }}</td>
                            <td style="white-space:nowrap">
                                <button class="ib" wire:click="editDept({{ $dept->id }})" aria-label="Edit department">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="ib" wire:click="deleteDept({{ $dept->id }})" wire:confirm="Delete this department?" aria-label="Delete department">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" style="color:var(--soft)">No departments yet — create your first one above.</td></tr>
                    @endforelse
                </table>
            </div>

        @elseif ($tab === 'roles')
            <div class="tools">
                <span style="flex:1"></span>
                <button class="btn pri" wire:click="startCreateRole">+ New role</button>
            </div>

            @if ($showCreateRole)
                <div class="card" style="padding:16px;margin-bottom:16px">
                    <form wire:submit="saveRole" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
                        <div style="flex:1;min-width:200px">
                            <label style="display:block;font-size:12px;margin-bottom:4px">Role name</label>
                            <input class="f" wire:model="roleName" placeholder="e.g. Supervisor">
                            @error('roleName') <small style="color:var(--bad)">{{ $message }}</small> @enderror
                        </div>
                        <label style="display:flex;align-items:center;gap:6px;min-width:180px">
                            <input type="checkbox" wire:model="roleCanManageBilling" style="width:18px;height:18px">
                            Can manage billing
                        </label>
                        <button type="submit" class="btn pri">{{ $editingRoleId ? 'Save' : 'Create' }}</button>
                        <button type="button" class="btn" wire:click="cancelRole">Cancel</button>
                    </form>
                </div>
            @endif

            <div class="card" style="padding:0">
                <table>
                    <tr><th>Role</th><th>Members</th><th>Can manage billing</th><th></th></tr>
                    @forelse ($this->roles as $role)
                        <tr wire:key="role-{{ $role->id }}">
                            <td>{{ $role->name }} @if($role->is_system)<span class="pill" style="margin-left:6px">Built-in</span>@endif</td>
                            <td>{{ $role->membersCount }}</td>
                            <td><span class="pill {{ $role->canManageBilling ? 'ok' : '' }}">{{ $role->canManageBilling ? 'Yes' : 'No' }}</span></td>
                            <td style="white-space:nowrap">
                                <button class="ib" wire:click="editRole({{ $role->id }})" aria-label="Edit role">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @unless($role->is_system)
                                    <button class="ib" wire:click="deleteRole({{ $role->id }})" wire:confirm="Delete this role?" aria-label="Delete role">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="color:var(--soft)">No roles yet — create your first one above.</td></tr>
                    @endforelse
                </table>
            </div>
        @endif
    </div>
</div>