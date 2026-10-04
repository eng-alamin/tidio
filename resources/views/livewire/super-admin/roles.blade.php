<div>
  <div class="page-head">
    <div><h2>Roles &amp; Permissions</h2><p>Who can sign in to the platform panel, and what they can do</p></div>
    <button class="btn btn-cobalt" type="button" wire:click="openCreate"><i class="bi bi-plus-lg" aria-hidden="true"></i>Add staff</button>
  </div>

  <div class="grid-4 mb-4">
    <div class="glass stat-card"><div class="label">Staff accounts</div><div class="value">{{ number_format($this->stats['total']) }}</div></div>
    <div class="glass stat-card"><div class="label">Super admins</div><div class="value">{{ number_format($this->stats['super_admin']) }}</div></div>
    <div class="glass stat-card"><div class="label">Billing admins</div><div class="value">{{ number_format($this->stats['billing_admin']) }}</div></div>
    <div class="glass stat-card"><div class="label">Support staff</div><div class="value">{{ number_format($this->stats['support_staff']) }}</div></div>
  </div>

  <div class="glass panel mb-4">
    <div class="d-flex gap-2 mb-3 flex-wrap">
      <label class="sr-only" for="staffSearch">Search staff</label>
      <input class="form-control" style="max-width:260px;" placeholder="Search by name or email…" id="staffSearch" wire:model.live.debounce.300ms="search">

      <select class="form-select" style="max-width:180px;" aria-label="Filter by role" wire:model.live="roleFilter">
        <option value="">All roles</option>
        <option value="super_admin">Super Admin</option>
        <option value="billing_admin">Billing Admin</option>
        <option value="support_staff">Support Staff</option>
      </select>
    </div>

    <div class="table-wrap" wire:loading.class="opacity-50" wire:target="search,roleFilter,gotoPage,previousPage,nextPage">
      <table class="responsive-table">
        <thead>
          <tr>
            <th scope="col">Name</th><th scope="col">Email</th><th scope="col">Role</th><th scope="col">Added</th>
            <th scope="col"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($this->staff as $member)
            @php($badge = $this->roleBadge($member->role))
            <tr wire:key="staff-{{ $member->id }}">
              <td data-label="Name" class="cell-main-td">{{ $member->name }} @if ($this->isSelf($member->id)) <span style="color:var(--text-soft); font-size:.76rem;">(you)</span> @endif</td>
              <td data-label="Email">{{ $member->email }}</td>
              <td data-label="Role"><span class="status-pill {{ $badge['class'] }}">{{ $badge['label'] }}</span></td>
              <td data-label="Added">{{ $member->created_at?->format('Y-m-d') }}</td>
              <td data-label="Actions">
                <div class="row-actions">
                  <button class="icon-action" type="button" wire:click="openEdit({{ $member->id }})" aria-label="Edit {{ $member->name }}"><i class="bi bi-pencil"></i></button>
                  <button class="icon-action" type="button" wire:click="openPassword({{ $member->id }})" aria-label="Reset password for {{ $member->name }}"><i class="bi bi-key"></i></button>
                  @unless ($this->isSelf($member->id))
                    <button class="icon-action danger" type="button" wire:click="confirmDelete({{ $member->id }})" aria-label="Delete {{ $member->name }}"><i class="bi bi-trash3"></i></button>
                  @endunless
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" style="padding:0;">
                <div class="state-block">
                  <div class="state-icon" aria-hidden="true"><i class="bi bi-shield-lock"></i></div>
                  <h6>No staff found</h6>
                  <p>Try a different search or filter.</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">{{ $this->staff->links() }}</div>
  </div>

  <div class="glass panel">
    <h5 style="margin-bottom:14px;">What each role can do</h5>
    <div class="table-wrap">
      <table class="responsive-table">
        <thead>
          <tr>
            <th scope="col">Action</th><th scope="col">Super Admin</th><th scope="col">Billing Admin</th><th scope="col">Support Staff</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($this->matrix() as $row)
            <tr>
              <td data-label="Action" class="cell-main-td">{{ $row['area'] }}</td>
              @foreach (['super_admin' => 'Super Admin', 'billing_admin' => 'Billing Admin', 'support_staff' => 'Support Staff'] as $key => $label)
                <td data-label="{{ $label }}">
                  @if ($row[$key])
                    <i class="bi bi-check-lg" style="color:#4ADE80;" aria-hidden="true"></i><span class="sr-only">Allowed</span>
                  @else
                    <i class="bi bi-dash" style="color:var(--text-soft);" aria-hidden="true"></i><span class="sr-only">Not allowed</span>
                  @endif
                </td>
              @endforeach
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- ------------------------------------------------------------ modals --}}
  @if ($modal)
    <div class="modal-overlay open" role="dialog" aria-modal="true" wire:key="staff-modal" wire:keydown.escape.window="closeModal">
      <div class="modal-box">
        <button class="icon-btn modal-close" type="button" wire:click="closeModal" aria-label="Close dialog"><i class="bi bi-x-lg"></i></button>

        @if ($modal === 'form')
          <h4>{{ $editingId ? 'Edit staff account' : 'Add staff' }}</h4>
          <div class="modal-sub">{{ $editingId ? 'Change their name, email or role.' : 'They sign in at /admin/login with this email and password.' }}</div>
          <form wire:submit="save">
            <div class="form-field">
              <label class="form-label" for="f-sname">Name</label>
              <input class="form-control @error('name') is-invalid @enderror" id="f-sname" wire:model="name" maxlength="100" autocomplete="off" autofocus>
              @error('name')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
              <label class="form-label" for="f-semail">Email</label>
              <input class="form-control @error('email') is-invalid @enderror" id="f-semail" type="email" wire:model="email" maxlength="255" autocomplete="off">
              @error('email')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
              <label class="form-label" for="f-srole">Role</label>
              <select class="form-select @error('role') is-invalid @enderror" id="f-srole" wire:model="role" @disabled($editingId && $this->isSelf($editingId))>
                <option value="super_admin">Super Admin</option>
                <option value="billing_admin">Billing Admin</option>
                <option value="support_staff">Support Staff</option>
              </select>
              @if ($editingId && $this->isSelf($editingId))<div class="modal-sub" style="margin:6px 0 0;">You can't change your own role.</div>@endif
              @error('role')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            @unless ($editingId)
              <div class="form-field">
                <label class="form-label" for="f-spass">Password</label>
                <input class="form-control @error('password') is-invalid @enderror" id="f-spass" type="password" wire:model="password" autocomplete="new-password">
                <div class="modal-sub" style="margin:6px 0 0;">At least 12 characters, with upper and lower case letters and a number.</div>
                @error('password')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
              </div>
              <div class="form-field">
                <label class="form-label" for="f-spass2">Confirm password</label>
                <input class="form-control @error('passwordConfirmation') is-invalid @enderror" id="f-spass2" type="password" wire:model="passwordConfirmation" autocomplete="new-password">
                @error('passwordConfirmation')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
              </div>
            @endunless

            <div class="modal-actions">
              <button type="button" class="btn btn-ghost" wire:click="closeModal">Cancel</button>
              <button type="submit" class="btn btn-cobalt" wire:loading.attr="disabled" wire:target="save">{{ $editingId ? 'Save changes' : 'Add staff' }}</button>
            </div>
          </form>

        @elseif ($modal === 'password' && $this->editing)
          <h4>Reset password for {{ $this->editing->name }}</h4>
          <div class="modal-sub">Their current password stops working and any "remember me" sign-in ends. Share the new password with them securely.</div>
          <form wire:submit="resetPassword">
            <div class="form-field">
              <label class="form-label" for="f-rpass">New password</label>
              <input class="form-control @error('password') is-invalid @enderror" id="f-rpass" type="password" wire:model="password" autocomplete="new-password" autofocus>
              <div class="modal-sub" style="margin:6px 0 0;">At least 12 characters, with upper and lower case letters and a number.</div>
              @error('password')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>
            <div class="form-field">
              <label class="form-label" for="f-rpass2">Confirm new password</label>
              <input class="form-control @error('passwordConfirmation') is-invalid @enderror" id="f-rpass2" type="password" wire:model="passwordConfirmation" autocomplete="new-password">
              @error('passwordConfirmation')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>
            <div class="modal-actions">
              <button type="button" class="btn btn-ghost" wire:click="closeModal">Cancel</button>
              <button type="submit" class="btn btn-cobalt" wire:loading.attr="disabled" wire:target="resetPassword">Reset password</button>
            </div>
          </form>

        @elseif ($modal === 'delete' && $this->editing)
          <div class="confirm-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></div>
          <h4>Delete {{ $this->editing->name }}?</h4>
          <div class="modal-sub">They lose access right away and the notes they wrote on tenants and users are deleted too. If they ever impersonated a user, the account is kept and this is refused.</div>
          <div class="modal-actions">
            <button type="button" class="btn btn-ghost" wire:click="closeModal">Keep account</button>
            <button type="button" class="btn btn-danger" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">Delete account</button>
          </div>
        @endif
      </div>
    </div>
  @endif
</div>
