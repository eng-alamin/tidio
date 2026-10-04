<div>
  <div class="page-head">
    <div><h2>Users</h2><p>All accounts across every tenant</p></div>
    @if ($this->canManage())
      <button class="btn btn-cobalt" type="button" wire:click="openCreate"><i class="bi bi-plus-lg" aria-hidden="true"></i>Add User</button>
    @endif
  </div>

  <div class="glass panel">
    <div class="d-flex gap-2 mb-3 flex-wrap">
      <label class="sr-only" for="userSearch">Search users</label>
      <input class="form-control" style="max-width:260px;" placeholder="Search users…" id="userSearch" wire:model.live.debounce.300ms="search">

      <select class="form-select" style="max-width:160px;" aria-label="Filter by role" wire:model.live="roleFilter">
        <option value="">All roles</option>
        @foreach ($this->roleNames as $roleName)
          <option value="{{ $roleName }}">{{ $roleName }}</option>
        @endforeach
      </select>

      <select class="form-select" style="max-width:160px;" aria-label="Filter by status" wire:model.live="statusFilter">
        <option value="">All statuses</option>
        <option value="active">Active</option>
        <option value="disabled">Disabled</option>
      </select>
    </div>

    <div class="table-wrap" wire:loading.class="opacity-50" wire:target="search,roleFilter,statusFilter,gotoPage,previousPage,nextPage">
      <table class="responsive-table">
        <thead>
          <tr>
            <th scope="col">User</th><th scope="col">Tenant</th><th scope="col">Role</th>
            <th scope="col">Status</th><th scope="col">Last active</th><th scope="col"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($this->users as $user)
            @php
              $first = $user->workspaces->first();
              $extra = $user->workspaces->count() - 1;
              $roleName = $first ? ($this->roleLabels[$first->pivot->role_id] ?? '—') : '—';
              $lastActive = $this->lastActive($user);
            @endphp
            <tr wire:key="user-{{ $user->id }}">
              <td data-label="User" class="cell-main-td">
                <div class="cell-main">
                  <div class="cell-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 2)) }}</div>
                  <div>
                    <div class="cell-title">{{ $user->name }}</div>
                    <div class="cell-sub">{{ $user->email }}</div>
                  </div>
                </div>
              </td>
              <td data-label="Tenant">
                @if ($first)
                  {{ $first->name }}@if ($extra > 0) <span style="color:var(--text-soft);">+{{ $extra }} more</span>@endif
                @else
                  <span style="color:var(--text-soft);">No tenant</span>
                @endif
              </td>
              <td data-label="Role">{{ $roleName }}@if ($user->owned_count > 0) <span class="plan-tag">Owner</span>@endif</td>
              <td data-label="Status">
                @if ($this->canManage())
                  <button class="status-toggle" type="button" wire:click="toggleDisabled({{ $user->id }})" aria-pressed="{{ $user->is_disabled ? 'false' : 'true' }}" aria-label="{{ $user->is_disabled ? 'Enable' : 'Disable' }} {{ $user->name }}">
                    <span class="switch {{ $user->is_disabled ? '' : 'on' }}"></span>
                    <span class="status-pill {{ $user->is_disabled ? 'status-suspended' : 'status-active' }}">{{ $user->is_disabled ? 'Disabled' : 'Active' }}</span>
                  </button>
                @else
                  <span class="status-pill {{ $user->is_disabled ? 'status-suspended' : 'status-active' }}">{{ $user->is_disabled ? 'Disabled' : 'Active' }}</span>
                @endif
              </td>
              <td data-label="Last active">{{ $lastActive ? $lastActive->diffForHumans() : '—' }}</td>
              <td data-label="Actions">
                <div class="row-actions">
                  @if ($this->canImpersonate() && ! $user->is_disabled)
                    <button class="icon-action" type="button" wire:click="confirmImpersonate({{ $user->id }})" aria-label="Impersonate {{ $user->name }}"><i class="bi bi-person-badge"></i></button>
                  @endif
                  @if ($this->canManage())
                    <button class="icon-action" type="button" wire:click="openEdit({{ $user->id }})" aria-label="Edit {{ $user->name }}"><i class="bi bi-pencil"></i></button>
                    <button class="icon-action danger" type="button" wire:click="confirmDelete({{ $user->id }})" aria-label="Delete {{ $user->name }}"><i class="bi bi-trash3"></i></button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" style="padding:0;">
                <div class="state-block">
                  <div class="state-icon" aria-hidden="true"><i class="bi bi-people"></i></div>
                  <h6>No users found</h6>
                  <p>Try a different search or filter{{ $this->canManage() ? ', or add a new user' : '' }}.</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">{{ $this->users->links() }}</div>
  </div>

  {{-- ------------------------------------------------------------ modals --}}
  @if ($modal)
    <div class="modal-overlay open" role="dialog" aria-modal="true" wire:key="user-modal" wire:keydown.escape.window="closeModal">
      <div class="modal-box">
        <button class="icon-btn modal-close" type="button" wire:click="closeModal" aria-label="Close dialog"><i class="bi bi-x-lg"></i></button>

        @if ($modal === 'form')
          @php $isEdit = $editingId !== null; @endphp
          <h4>{{ $isEdit ? 'Edit user' : 'Add a user' }}</h4>
          <div class="modal-sub">{{ $isEdit ? 'Update this account.' : 'Create an account and add it to a tenant workspace.' }}</div>

          <form wire:submit="save" novalidate>
            <div class="form-field">
              <label class="form-label" for="f-uname">Full name</label>
              <input class="form-control @error('name') is-invalid @enderror" id="f-uname" wire:model="name" placeholder="e.g. Jordan Lee" autofocus>
              @error('name')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
              <label class="form-label" for="f-uemail">Email address</label>
              <input class="form-control @error('email') is-invalid @enderror" id="f-uemail" type="email" wire:model="email" placeholder="name@company.com" autocomplete="off">
              @error('email')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
              <label class="form-label" for="f-upass">{{ $isEdit ? 'New password (optional)' : 'Temporary password' }}</label>
              <input class="form-control @error('password') is-invalid @enderror" id="f-upass" type="password" wire:model="password" placeholder="{{ $isEdit ? 'Leave blank to keep current' : 'At least 8 characters' }}" autocomplete="new-password">
              @error('password')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            @unless ($isEdit)
              <div class="form-field">
                <label class="form-label" for="f-utenant">Tenant</label>
                <select class="form-select @error('workspaceId') is-invalid @enderror" id="f-utenant" wire:model.live="workspaceId">
                  @foreach ($this->workspaces as $workspace)
                    <option value="{{ $workspace->id }}">{{ $workspace->name }}</option>
                  @endforeach
                </select>
                @error('workspaceId')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
              </div>

              <div class="form-field">
                <label class="form-label" for="f-urole">Role</label>
                <select class="form-select @error('roleId') is-invalid @enderror" id="f-urole" wire:model="roleId">
                  @foreach ($this->formRoles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                  @endforeach
                </select>
                @error('roleId')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
              </div>
            @endunless

            <div class="modal-actions">
              <button type="button" class="btn btn-ghost" wire:click="closeModal">Cancel</button>
              <button type="submit" class="btn btn-cobalt" wire:loading.attr="disabled" wire:target="save">{{ $isEdit ? 'Save changes' : 'Create user' }}</button>
            </div>
          </form>

        @elseif ($modal === 'impersonate' && $this->editingUser)
          @php $options = $this->impersonationWorkspaces; @endphp
          <div class="confirm-icon" aria-hidden="true"><i class="bi bi-person-badge"></i></div>
          <h4>Impersonate {{ $this->editingUser->name }}?</h4>
          <div class="modal-sub">You'll see the app exactly as this user does, and anything you change is real. A red banner stays on screen, the session is logged under your name, and it ends after {{ \App\Services\SuperAdmin\ImpersonationService::TTL_MINUTES }} minutes.</div>

          @if ($options->isEmpty())
            <div class="modal-sub">This user has no active, unsuspended workspace to impersonate in.</div>
            <div class="modal-actions"><button type="button" class="btn btn-ghost" wire:click="closeModal">Close</button></div>
          @else
            @if ($options->count() > 1)
              <div class="form-field">
                <label class="form-label" for="f-iws">Workspace</label>
                <select class="form-select" id="f-iws" wire:model="impersonateWorkspaceId">
                  @foreach ($options as $option)
                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                  @endforeach
                </select>
              </div>
            @else
              <div class="detail-row"><span class="k">Workspace</span><span>{{ $options->first()->name }}</span></div>
            @endif
            <div class="modal-actions">
              <button type="button" class="btn btn-ghost" wire:click="closeModal">Cancel</button>
              <button type="button" class="btn btn-cobalt" wire:click="startImpersonation" wire:loading.attr="disabled" wire:target="startImpersonation">Start impersonating</button>
            </div>
          @endif

        @elseif ($modal === 'delete')
          <div class="confirm-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></div>
          <h4>Delete {{ $this->editingUser?->name }}?</h4>
          <div class="modal-sub">They lose access to every workspace immediately. Tenant owners can't be deleted — remove or hand over their tenant first.</div>
          <div class="modal-actions">
            <button type="button" class="btn btn-ghost" wire:click="closeModal">Cancel</button>
            <button type="button" class="btn btn-danger" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">Delete user</button>
          </div>
        @endif
      </div>
    </div>
  @endif
</div>
