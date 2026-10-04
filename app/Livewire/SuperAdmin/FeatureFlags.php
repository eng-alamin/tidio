<?php

namespace App\Livewire\SuperAdmin;

use App\Enums\SuperAdminRole;
use App\Models\FeatureFlag;
use App\Models\SuperAdmin;
use App\Models\Workspace;
use App\Services\SuperAdmin\FeatureFlagService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
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
#[Title('Feature Flags')]
class FeatureFlags extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** null | create | overrides | delete */
    public ?string $modal = null;

    /** Id of the global row of the flag being managed. */
    public ?int $flagId = null;

    public string $newKey = '';

    public string $tenantSearch = '';

    public ?int $overrideWorkspaceId = null;

    /** on | off */
    public string $overrideState = 'on';

    // ---------------------------------------------------------------- data

    /** @return array{total:int, on:int, off:int, overrides:int} */
    #[Computed]
    public function stats(): array
    {
        $global = fn () => FeatureFlag::query()->whereNull('workspace_id');

        return [
            'total' => $global()->count(),
            'on' => $global()->where('is_enabled', true)->count(),
            'off' => $global()->where('is_enabled', false)->count(),
            'overrides' => FeatureFlag::query()->whereNotNull('workspace_id')->count(),
        ];
    }

    #[Computed]
    public function flags(): LengthAwarePaginator
    {
        $search = trim($this->search);

        $page = FeatureFlag::query()
            ->whereNull('workspace_id')
            ->when($search !== '', fn ($q) => $q->where('key', 'like', '%'.$search.'%'))
            ->orderBy('key')
            ->paginate(10);

        // One extra query for the whole page instead of one per row.
        $overrides = FeatureFlag::query()
            ->whereNotNull('workspace_id')
            ->whereIn('key', $page->getCollection()->pluck('key'))
            ->get(['key', 'is_enabled'])
            ->groupBy('key');

        $page->getCollection()->each(function (FeatureFlag $flag) use ($overrides) {
            $rows = $overrides->get($flag->key, collect());
            $flag->setAttribute('overrides_on', $rows->where('is_enabled', true)->count());
            $flag->setAttribute('overrides_off', $rows->where('is_enabled', false)->count());
        });

        return $page;
    }

    #[Computed]
    public function selected(): ?FeatureFlag
    {
        return $this->flagId
            ? FeatureFlag::query()->whereNull('workspace_id')->find($this->flagId)
            : null;
    }

    /** @return Collection<int, FeatureFlag> */
    #[Computed]
    public function overrides(): Collection
    {
        if (! $this->selected) {
            return new Collection;
        }

        return FeatureFlag::query()
            ->whereNotNull('workspace_id')
            ->where('key', $this->selected->key)
            ->with(['workspace' => fn ($q) => $q->withTrashed()->select('id', 'name', 'deleted_at')])
            ->orderBy('id')
            ->get();
    }

    /** Tenants matching the search box that don't already have an override. */
    #[Computed]
    public function tenantMatches(): Collection
    {
        $term = trim($this->tenantSearch);

        if (mb_strlen($term) < 2 || ! $this->selected) {
            return new Collection;
        }

        return Workspace::query()
            ->where('name', 'like', '%'.$term.'%')
            ->whereNotIn('id', $this->overrides->pluck('workspace_id'))
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name']);
    }

    #[Computed]
    public function chosenTenant(): ?Workspace
    {
        return $this->overrideWorkspaceId ? Workspace::query()->find($this->overrideWorkspaceId, ['id', 'name']) : null;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    // ---------------------------------------------------------- permissions

    /** Flags change platform behaviour for everyone, so only super admins manage them. */
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

        $this->resetModalState();
        $this->modal = 'create';
    }

    public function openOverrides(int $id): void
    {
        $this->resetModalState();
        $this->flagId = FeatureFlag::query()->whereNull('workspace_id')->findOrFail($id)->id;
        $this->modal = 'overrides';
    }

    public function confirmDelete(int $id): void
    {
        abort_unless($this->canManage(), 403);

        $this->resetModalState();
        $this->flagId = FeatureFlag::query()->whereNull('workspace_id')->findOrFail($id)->id;
        $this->modal = 'delete';
    }

    public function closeModal(): void
    {
        $this->modal = null;
        $this->resetModalState();
    }

    private function resetModalState(): void
    {
        $this->reset(['flagId', 'newKey', 'tenantSearch', 'overrideWorkspaceId']);
        $this->overrideState = 'on';
        $this->resetValidation();
        unset($this->selected, $this->overrides, $this->tenantMatches, $this->chosenTenant);
    }

    public function chooseTenant(int $workspaceId): void
    {
        abort_unless($this->canManage(), 403);

        $this->overrideWorkspaceId = Workspace::query()->findOrFail($workspaceId)->id;
        $this->tenantSearch = '';
        unset($this->tenantMatches, $this->chosenTenant);
    }

    public function clearTenant(): void
    {
        $this->overrideWorkspaceId = null;
        unset($this->chosenTenant);
    }

    // ------------------------------------------------------------- actions

    public function create(FeatureFlagService $service): void
    {
        abort_unless($this->canManage(), 403);

        $this->newKey = strtolower(trim($this->newKey));

        $this->validate([
            'newKey' => [
                'required', 'string', 'min:3', 'max:100', 'regex:/^[a-z0-9][a-z0-9_.-]*$/',
                Rule::unique('feature_flags', 'key')->whereNull('workspace_id'),
            ],
        ], [
            'newKey.regex' => 'Use lowercase letters, numbers, dashes, dots and underscores. Start with a letter or number.',
            'newKey.unique' => 'A flag with this key already exists.',
        ]);

        try {
            $service->create($this->admin(), $this->newKey);
        } catch (RuntimeException $e) {
            $this->addError('newKey', $e->getMessage());

            return;
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not create the flag. Please try again.', type: 'error');

            return;
        }

        $this->resetPage();
        $this->dispatch('toast', message: "{$this->newKey} created. It starts switched off.", type: 'ok');
        $this->closeModal();
    }

    public function toggleGlobal(int $id, FeatureFlagService $service): void
    {
        abort_unless($this->canManage(), 403);

        $flag = FeatureFlag::query()->whereNull('workspace_id')->findOrFail($id);
        $enable = ! $flag->is_enabled;

        try {
            $service->setGlobal($this->admin(), $flag, $enable);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');

            return;
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not update the flag.', type: 'error');

            return;
        }

        $this->dispatch('toast', message: "{$flag->key} is now ".($enable ? 'on' : 'off').' for everyone without an override.', type: $enable ? 'ok' : 'warn');
    }

    public function addOverride(FeatureFlagService $service): void
    {
        abort_unless($this->canManage(), 403);

        if (! $this->selected || ! $this->overrideWorkspaceId) {
            $this->dispatch('toast', message: 'Choose a tenant first.', type: 'error');

            return;
        }

        $this->validate(['overrideState' => ['required', Rule::in(['on', 'off'])]]);

        try {
            $service->setOverride($this->admin(), $this->selected, $this->overrideWorkspaceId, $this->overrideState === 'on');
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');

            return;
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not save the override.', type: 'error');

            return;
        }

        $name = $this->chosenTenant?->name ?? 'Tenant';
        $this->reset(['overrideWorkspaceId', 'tenantSearch']);
        $this->overrideState = 'on';
        unset($this->overrides, $this->tenantMatches, $this->chosenTenant);

        $this->dispatch('toast', message: "Override saved for {$name}.", type: 'ok');
    }

    public function flipOverride(int $overrideId, FeatureFlagService $service): void
    {
        abort_unless($this->canManage(), 403);

        $override = FeatureFlag::query()->whereNotNull('workspace_id')->findOrFail($overrideId);

        // Only overrides of the flag open in the modal can be changed from it.
        abort_unless($this->selected && $override->key === $this->selected->key, 403);

        try {
            $service->setOverride($this->admin(), $this->selected, $override->workspace_id, ! $override->is_enabled);
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not update the override.', type: 'error');

            return;
        }

        unset($this->overrides);
    }

    public function removeOverride(int $overrideId, FeatureFlagService $service): void
    {
        abort_unless($this->canManage(), 403);

        $override = FeatureFlag::query()->whereNotNull('workspace_id')->findOrFail($overrideId);
        abort_unless($this->selected && $override->key === $this->selected->key, 403);

        try {
            $service->removeOverride($this->admin(), $override);
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not remove the override.', type: 'error');

            return;
        }

        unset($this->overrides, $this->tenantMatches);
        $this->dispatch('toast', message: 'Override removed. That tenant follows the global setting again.', type: 'ok');
    }

    public function delete(FeatureFlagService $service): void
    {
        abort_unless($this->canManage(), 403);

        $flag = FeatureFlag::query()->whereNull('workspace_id')->find($this->flagId);

        if (! $flag) {
            $this->closeModal();

            return;
        }

        try {
            $service->delete($this->admin(), $flag);
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not delete the flag.', type: 'error');

            return;
        }

        $this->dispatch('toast', message: "{$flag->key} deleted.", type: 'warn');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.super-admin.feature-flags');
    }
}
