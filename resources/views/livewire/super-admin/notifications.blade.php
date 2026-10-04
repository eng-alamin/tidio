<div>
  <div class="page-head">
    <div><h2>Notifications</h2><p>Announcements for tenants, such as maintenance notices</p></div>
    @if ($this->canManage())
      <button class="btn btn-cobalt" type="button" wire:click="openCreate"><i class="bi bi-plus-lg" aria-hidden="true"></i>New announcement</button>
    @endif
  </div>

  <div class="grid-4 mb-4">
    <div class="glass stat-card"><div class="label">Live now</div><div class="value">{{ number_format($this->stats['live']) }}</div></div>
    <div class="glass stat-card"><div class="label">Scheduled</div><div class="value">{{ number_format($this->stats['scheduled']) }}</div></div>
    <div class="glass stat-card"><div class="label">Ended</div><div class="value">{{ number_format($this->stats['ended']) }}</div></div>
    <div class="glass stat-card"><div class="label">Switched off</div><div class="value">{{ number_format($this->stats['inactive']) }}</div></div>
  </div>

  <div class="glass panel">
    <div class="d-flex gap-2 mb-3 flex-wrap">
      <label class="sr-only" for="annSearch">Search announcements</label>
      <input class="form-control" style="max-width:260px;" placeholder="Search by title…" id="annSearch" wire:model.live.debounce.300ms="search">

      <select class="form-select" style="max-width:170px;" aria-label="Filter by status" wire:model.live="statusFilter">
        <option value="">All statuses</option>
        <option value="live">Live</option>
        <option value="scheduled">Scheduled</option>
        <option value="ended">Ended</option>
        <option value="inactive">Switched off</option>
      </select>
    </div>

    <div class="table-wrap" wire:loading.class="opacity-50" wire:target="search,statusFilter,gotoPage,previousPage,nextPage">
      <table class="responsive-table">
        <thead>
          <tr>
            <th scope="col">Announcement</th><th scope="col">Audience</th><th scope="col">Shown</th><th scope="col">Status</th>
            <th scope="col"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($this->announcements as $announcement)
            @php $badge = $this->status($announcement); @endphp
            <tr wire:key="ann-{{ $announcement->id }}">
              <td data-label="Announcement" class="cell-main-td">{{ $announcement->title }}<div style="color:var(--text-soft); font-size:.76rem;">{{ \Illuminate\Support\Str::limit($announcement->body, 80) }}</div></td>
              <td data-label="Audience">{{ $this->audienceLabel($announcement->audience) }}</td>
              <td data-label="Shown">{{ $this->windowLabel($announcement) }}</td>
              <td data-label="Status">
                @if ($this->canManage())
                  <button class="toggle-switch {{ $announcement->is_active ? 'on' : '' }}" type="button" role="switch"
                          aria-checked="{{ $announcement->is_active ? 'true' : 'false' }}"
                          aria-label="{{ $announcement->is_active ? 'Switch off' : 'Switch on' }} {{ $announcement->title }}"
                          wire:click="toggleActive({{ $announcement->id }})" wire:loading.attr="disabled" wire:target="toggleActive({{ $announcement->id }})"></button>
                @endif
                <span class="status-pill {{ $badge['class'] }}">{{ $badge['label'] }}</span>
              </td>
              <td data-label="Actions">
                <div class="row-actions">
                  @if ($this->canManage())
                    <button class="icon-action" type="button" wire:click="openEdit({{ $announcement->id }})" aria-label="Edit {{ $announcement->title }}"><i class="bi bi-pencil"></i></button>
                    <button class="icon-action danger" type="button" wire:click="confirmDelete({{ $announcement->id }})" aria-label="Delete {{ $announcement->title }}"><i class="bi bi-trash3"></i></button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" style="padding:0;">
                <div class="state-block">
                  <div class="state-icon" aria-hidden="true"><i class="bi bi-bell"></i></div>
                  <h6>No announcements found</h6>
                  <p>{{ $this->canManage() ? 'Try a different search, or create a new announcement.' : 'Try a different search or filter.' }}</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">{{ $this->announcements->links() }}</div>
  </div>

  {{-- ------------------------------------------------------------ modals --}}
  @if ($modal)
    <div class="modal-overlay open" role="dialog" aria-modal="true" wire:key="ann-modal" wire:keydown.escape.window="closeModal">
      <div class="modal-box">
        <button class="icon-btn modal-close" type="button" wire:click="closeModal" aria-label="Close dialog"><i class="bi bi-x-lg"></i></button>

        @if ($modal === 'form')
          <h4>{{ $editingId ? 'Edit announcement' : 'New announcement' }}</h4>
          <div class="modal-sub">Leave the dates empty to show it as soon as it is switched on, with no end.</div>
          <form wire:submit="save">
            <div class="form-field">
              <label class="form-label" for="f-atitle">Title</label>
              <input class="form-control @error('title') is-invalid @enderror" id="f-atitle" wire:model="title" maxlength="150" autocomplete="off" autofocus>
              @error('title')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
              <label class="form-label" for="f-abody">Message</label>
              <textarea class="form-control @error('body') is-invalid @enderror" id="f-abody" rows="4" wire:model="body" maxlength="2000"></textarea>
              @error('body')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
              <label class="form-label" for="f-aaud">Audience</label>
              <select class="form-select @error('audience') is-invalid @enderror" id="f-aaud" wire:model="audience">
                <option value="all">Everyone</option>
                <option value="workspace_owners">Workspace owners</option>
                <option value="trial_users">Trial users</option>
              </select>
              @error('audience')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
              <label class="form-label" for="f-astart">Starts (optional)</label>
              <input class="form-control @error('startsAt') is-invalid @enderror" id="f-astart" type="datetime-local" wire:model="startsAt">
              @error('startsAt')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
              <label class="form-label" for="f-aend">Ends (optional)</label>
              <input class="form-control @error('endsAt') is-invalid @enderror" id="f-aend" type="datetime-local" wire:model="endsAt">
              @error('endsAt')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="f-aactive" wire:model="isActive">
                <label class="form-check-label" for="f-aactive">Switched on</label>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" class="btn btn-ghost" wire:click="closeModal">Cancel</button>
              <button type="submit" class="btn btn-cobalt" wire:loading.attr="disabled" wire:target="save">{{ $editingId ? 'Save changes' : 'Create announcement' }}</button>
            </div>
          </form>

        @elseif ($modal === 'delete' && $this->editing)
          <div class="confirm-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></div>
          <h4>Delete "{{ $this->editing->title }}"?</h4>
          <div class="modal-sub">It is removed for good. To hide it for now, switch it off instead.</div>
          <div class="modal-actions">
            <button type="button" class="btn btn-ghost" wire:click="closeModal">Keep it</button>
            <button type="button" class="btn btn-danger" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">Delete announcement</button>
          </div>
        @endif
      </div>
    </div>
  @endif
</div>
