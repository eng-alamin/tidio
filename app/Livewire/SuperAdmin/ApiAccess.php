<?php

namespace App\Livewire\SuperAdmin;

use App\Enums\SuperAdminRole;
use App\Models\SuperAdmin;
use App\Models\Webhook;
use App\Models\Workspace;
use App\Services\SuperAdmin\ApiAccessService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;
use Throwable;

#[Layout('layouts.super-admin')]
#[Title('API Keys & Webhooks')]
class ApiAccess extends Component
{
    use WithPagination;

    private const TABS = ['keys', 'webhooks'];

    private const KEYS_PAGE = 'keysPage';

    private const HOOKS_PAGE = 'hooksPage';

    protected string $paginationTheme = 'bootstrap';

    /** keys | webhooks */
    #[Url(as: 'tab', except: 'keys')]
    public string $tab = 'keys';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** Webhooks tab only: active | paused */
    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    /** null | rotate | delete-webhook */
    public ?string $modal = null;

    public ?int $selectedId = null;

    // ---------------------------------------------------------------- data

    /** @return array{keys:int, active:int, paused:int, tenants:int} */
    #[Computed]
    public function stats(): array
    {
        $hooks = Webhook::query()
            ->whereHas('workspace')
            ->selectRaw('count(*) as total, coalesce(sum(case when is_active then 1 else 0 end), 0) as active, count(distinct workspace_id) as tenants')
            ->first();

        $total = (int) ($hooks->total ?? 0);
        $active = (int) ($hooks->active ?? 0);

        return [
            'keys' => Workspace::query()->whereNotNull('api_key')->count(),
            'active' => $active,
            'paused' => $total - $active,
            'tenants' => (int) ($hooks->tenants ?? 0),
        ];
    }

    #[Computed]
    public function keys(): LengthAwarePaginator
    {
        $search = trim($this->search);

        return Workspace::query()
            ->select(['id', 'name', 'slug', 'api_key', 'is_suspended'])
            ->whereNotNull('api_key')
            ->withCount('webhooks')
            ->when($search !== '', fn (Builder $q) => $q->where(function (Builder $w) use ($search) {
                $w->where('name', 'like', '%'.$search.'%')->orWhere('slug', 'like', '%'.$search.'%');
            }))
            ->orderBy('name')
            ->paginate(10, ['*'], self::KEYS_PAGE);
    }

    #[Computed]
    public function webhooks(): LengthAwarePaginator
    {
        $search = trim($this->search);

        return Webhook::query()
            ->with('workspace:id,name,is_suspended')
            ->whereHas('workspace')
            ->when($search !== '', fn (Builder $q) => $q->where(function (Builder $w) use ($search) {
                $w->where('url', 'like', '%'.$search.'%')
                    ->orWhereHas('workspace', fn (Builder $t) => $t->where('name', 'like', '%'.$search.'%'));
            }))
            ->when($this->statusFilter === 'active', fn (Builder $q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'paused', fn (Builder $q) => $q->where('is_active', false))
            ->latest('id')
            ->paginate(10, ['*'], self::HOOKS_PAGE);
    }

    #[Computed]
    public function selectedWorkspace(): ?Workspace
    {
        return $this->modal === 'rotate' && $this->selectedId
            ? Workspace::query()->select(['id', 'name'])->find($this->selectedId)
            : null;
    }

    #[Computed]
    public function selectedWebhook(): ?Webhook
    {
        return $this->modal === 'delete-webhook' && $this->selectedId
            ? Webhook::query()->with('workspace:id,name')->find($this->selectedId)
            : null;
    }

    // ----------------------------------------------------------- lifecycle

    public function mount(): void
    {
        if (! in_array($this->tab, self::TABS, true)) {
            $this->tab = 'keys';
        }
        if (! in_array($this->statusFilter, ['', 'active', 'paused'], true)) {
            $this->statusFilter = '';
        }
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, self::TABS, true) ? $tab : 'keys';
        $this->search = '';
        $this->statusFilter = '';
        $this->resetPages();
    }

    public function updatingSearch(): void
    {
        $this->resetPages();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPages();
    }

    private function resetPages(): void
    {
        $this->resetPage(self::KEYS_PAGE);
        $this->resetPage(self::HOOKS_PAGE);
    }

    // ------------------------------------------------------------ presenters

    public function maskedKey(?string $key): string
    {
        return ApiAccessService::maskKey($key);
    }

    public function endpoint(?string $url): string
    {
        return ApiAccessService::displayUrl($url);
    }

    /** @return array{label:string, class:string} */
    public function tenantStatus(?Workspace $workspace): array
    {
        return $workspace?->is_suspended
            ? ['label' => 'Suspended', 'class' => 'status-suspended']
            : ['label' => 'Active', 'class' => 'status-active'];
    }

    // ---------------------------------------------------------- permissions

    /** Keys and webhooks can leak data or break a tenant's integration, so only super admins change them. */
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

    public function confirmRotate(int $workspaceId): void
    {
        abort_unless($this->canManage(), 403);

        $this->selectedId = Workspace::query()->whereNotNull('api_key')->findOrFail($workspaceId)->id;
        $this->modal = 'rotate';
        unset($this->selectedWorkspace, $this->selectedWebhook);
    }

    public function confirmDeleteWebhook(int $webhookId): void
    {
        abort_unless($this->canManage(), 403);

        $this->selectedId = Webhook::query()->findOrFail($webhookId)->id;
        $this->modal = 'delete-webhook';
        unset($this->selectedWorkspace, $this->selectedWebhook);
    }

    public function closeModal(): void
    {
        $this->modal = null;
        $this->selectedId = null;
        unset($this->selectedWorkspace, $this->selectedWebhook);
    }

    // ------------------------------------------------------------- actions

    public function rotate(ApiAccessService $service): void
    {
        abort_unless($this->canManage(), 403);
        abort_unless($this->modal === 'rotate' && $this->selectedId !== null, 422);

        try {
            $workspace = $service->rotateKey($this->admin(), $this->selectedId);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
            $this->closeModal();

            return;
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not rotate the key. Please try again.', type: 'error');

            return;
        }

        $this->dispatch('toast', message: "{$workspace->name}'s API key was rotated. The old key no longer works.", type: 'warn');
        $this->closeModal();
    }

    public function toggleWebhook(int $webhookId, ApiAccessService $service): void
    {
        abort_unless($this->canManage(), 403);

        $webhook = Webhook::query()->findOrFail($webhookId);
        $makeActive = ! $webhook->is_active;

        try {
            $service->setWebhookActive($this->admin(), $webhook->id, $makeActive);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');

            return;
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not update the webhook.', type: 'error');

            return;
        }

        $this->dispatch('toast', message: $makeActive ? 'Webhook resumed.' : 'Webhook paused.', type: $makeActive ? 'ok' : 'warn');
    }

    public function deleteWebhook(ApiAccessService $service): void
    {
        abort_unless($this->canManage(), 403);
        abort_unless($this->modal === 'delete-webhook' && $this->selectedId !== null, 422);

        try {
            $service->deleteWebhook($this->admin(), $this->selectedId);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
            $this->closeModal();

            return;
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not delete the webhook.', type: 'error');

            return;
        }

        $this->dispatch('toast', message: 'Webhook deleted.', type: 'warn');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.super-admin.api-access');
    }
}
