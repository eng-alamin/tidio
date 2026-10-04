<div>
  <div class="page-head">
    <div><h2>Integrations</h2><p>The catalogue of apps shown on the public website</p></div>
    @if ($this->canManage())
      <button class="btn btn-cobalt" type="button" wire:click="openCreate"><i class="bi bi-plus-lg" aria-hidden="true"></i>New integration</button>
    @endif
  </div>

  <div class="grid-4 mb-4">
    <div class="glass stat-card"><div class="label">Integrations</div><div class="value">{{ number_format($this->stats['total']) }}</div></div>
    <div class="glass stat-card"><div class="label">Featured</div><div class="value">{{ number_format($this->stats['featured']) }}</div></div>
    <div class="glass stat-card"><div class="label">Categories</div><div class="value">{{ number_format($this->stats['categories']) }}</div></div>
  </div>

  <div class="glass panel">
    <div class="d-flex gap-2 mb-3 flex-wrap">
      <label class="sr-only" for="intSearch">Search integrations</label>
      <input class="form-control" style="max-width:260px;" placeholder="Search by name or slug…" id="intSearch" wire:model.live.debounce.300ms="search">

      <select class="form-select" style="max-width:190px;" aria-label="Filter by category" wire:model.live="categoryFilter">
        <option value="">All categories</option>
        @foreach ($this->categories as $cat)
          <option value="{{ $cat }}">{{ $cat }}</option>
        @endforeach
      </select>

      <select class="form-select" style="max-width:170px;" aria-label="Filter by featured" wire:model.live="featuredFilter">
        <option value="">All</option>
        <option value="featured">Featured</option>
        <option value="standard">Not featured</option>
      </select>
    </div>

    <div class="table-wrap" wire:loading.class="opacity-50" wire:target="search,categoryFilter,featuredFilter,gotoPage,previousPage,nextPage">
      <table class="responsive-table">
        <thead>
          <tr>
            <th scope="col">Integration</th><th scope="col">Category</th><th scope="col">Slug</th><th scope="col">Featured</th>
            <th scope="col"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($this->integrations as $integration)
            <tr wire:key="int-{{ $integration->id }}">
              <td data-label="Integration" class="cell-main-td">
                <span class="d-inline-flex align-items-center gap-2">
                  @if ($integration->logo && \Illuminate\Support\Str::startsWith($integration->logo, ['http://', 'https://']))
                    <img src="{{ $integration->logo }}" alt="" width="24" height="24" loading="lazy" referrerpolicy="no-referrer" style="object-fit:contain; border-radius:6px;">
                  @else
                    <i class="bi bi-plug-fill" aria-hidden="true" style="color:var(--text-soft);"></i>
                  @endif
                  <span>{{ $integration->name }}</span>
                </span>
                @if ($integration->description)
                  <div style="color:var(--text-soft); font-size:.76rem;">{{ \Illuminate\Support\Str::limit($integration->description, 80) }}</div>
                @endif
              </td>
              <td data-label="Category">{{ $integration->category ?: '—' }}</td>
              <td data-label="Slug"><code style="font-size:.8rem;">{{ $integration->slug }}</code></td>
              <td data-label="Featured">
                @if ($this->canManage())
                  <button class="toggle-switch {{ $integration->is_featured ? 'on' : '' }}" type="button" role="switch"
                          aria-checked="{{ $integration->is_featured ? 'true' : 'false' }}"
                          aria-label="{{ $integration->is_featured ? 'Unfeature' : 'Feature' }} {{ $integration->name }}"
                          wire:click="toggleFeatured({{ $integration->id }})" wire:loading.attr="disabled" wire:target="toggleFeatured({{ $integration->id }})"></button>
                @else
                  <span class="status-pill {{ $integration->is_featured ? 'status-active' : 'status-suspended' }}">{{ $integration->is_featured ? 'Featured' : 'No' }}</span>
                @endif
              </td>
              <td data-label="Actions">
                <div class="row-actions">
                  @if ($this->canManage())
                    <button class="icon-action" type="button" wire:click="openEdit({{ $integration->id }})" aria-label="Edit {{ $integration->name }}"><i class="bi bi-pencil"></i></button>
                    <button class="icon-action danger" type="button" wire:click="confirmDelete({{ $integration->id }})" aria-label="Delete {{ $integration->name }}"><i class="bi bi-trash3"></i></button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" style="padding:0;">
                <div class="state-block">
                  <div class="state-icon" aria-hidden="true"><i class="bi bi-plug"></i></div>
                  <h6>No integrations found</h6>
                  <p>{{ $this->canManage() ? 'Try a different search, or add a new integration.' : 'Try a different search or filter.' }}</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">{{ $this->integrations->links() }}</div>
  </div>

  {{-- ------------------------------------------------------------ modals --}}
  @if ($modal)
    <div class="modal-overlay open" role="dialog" aria-modal="true" wire:key="int-modal" wire:keydown.escape.window="closeModal">
      <div class="modal-box">
        <button class="icon-btn modal-close" type="button" wire:click="closeModal" aria-label="Close dialog"><i class="bi bi-x-lg"></i></button>

        @if ($modal === 'form')
          <h4>{{ $editingId ? 'Edit integration' : 'New integration' }}</h4>
          <div class="modal-sub">Shown on the public integrations page. The slug is part of its web address{{ $editingId ? ' and can\'t be changed.' : ', so it can\'t be changed later.' }}</div>
          <form wire:submit="save">
            <div class="form-field">
              <label class="form-label" for="f-iname">Name</label>
              <input class="form-control @error('name') is-invalid @enderror" id="f-iname" wire:model.live.debounce.300ms="name" maxlength="100" placeholder="e.g. Shopify" autocomplete="off" autofocus>
              @error('name')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
              <label class="form-label" for="f-islug">Slug</label>
              <input class="form-control @error('slug') is-invalid @enderror" id="f-islug" wire:model="slug" maxlength="100" placeholder="e.g. shopify" autocomplete="off" @disabled($editingId)>
              @error('slug')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
              <label class="form-label" for="f-icat">Category</label>
              <input class="form-control @error('category') is-invalid @enderror" id="f-icat" wire:model="category" list="intCategories" maxlength="50" placeholder="e.g. ecommerce" autocomplete="off">
              <datalist id="intCategories">
                @foreach ($this->categories as $cat)<option value="{{ $cat }}"></option>@endforeach
              </datalist>
              @error('category')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
              <label class="form-label" for="f-ilogo">Logo web address (optional)</label>
              <input class="form-control @error('logo') is-invalid @enderror" id="f-ilogo" wire:model="logo" maxlength="255" placeholder="https://…" autocomplete="off">
              @error('logo')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
              <label class="form-label" for="f-idesc">Description (optional)</label>
              <textarea class="form-control @error('description') is-invalid @enderror" id="f-idesc" rows="3" wire:model="description" maxlength="1000"></textarea>
              @error('description')<div class="field-error show" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="f-ifeat" wire:model="isFeatured">
                <label class="form-check-label" for="f-ifeat">Feature on the public site</label>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" class="btn btn-ghost" wire:click="closeModal">Cancel</button>
              <button type="submit" class="btn btn-cobalt" wire:loading.attr="disabled" wire:target="save">{{ $editingId ? 'Save changes' : 'Add integration' }}</button>
            </div>
          </form>

        @elseif ($modal === 'delete' && $this->editing)
          <div class="confirm-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></div>
          <h4>Delete {{ $this->editing->name }}?</h4>
          <div class="modal-sub">It disappears from the public integrations page. The slug <code>{{ $this->editing->slug }}</code> stays reserved.</div>
          <div class="modal-actions">
            <button type="button" class="btn btn-ghost" wire:click="closeModal">Keep it</button>
            <button type="button" class="btn btn-danger" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">Delete integration</button>
          </div>
        @endif
      </div>
    </div>
  @endif
</div>
