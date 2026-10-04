<?php

namespace App\Livewire\SuperAdmin;

use App\Enums\SuperAdminRole;
use App\Models\SuperAdmin;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

/**
 * Read-only trail of everything platform staff do in the Super Admin panel.
 * Rows are never edited or deleted from here.
 */
#[Layout('layouts.super-admin')]
#[Title('Activity & Audit Log')]
class ActivityLog extends Component
{
    use WithPagination;

    /** log_name => [label, icon] */
    private const TYPES = [
        'tenant' => ['Tenants', 'bi-buildings-fill'],
        'user' => ['Users', 'bi-person-fill'],
        'subscription' => ['Subscriptions', 'bi-credit-card-2-front-fill'],
        'invoice' => ['Billing', 'bi-receipt'],
        'coupon' => ['Coupons', 'bi-ticket-perforated-fill'],
        'feature_flag' => ['Feature flags', 'bi-toggles'],
        'api_key' => ['API keys', 'bi-key-fill'],
        'webhook' => ['Webhooks', 'bi-plug-fill'],
        'integration' => ['Integrations', 'bi-plug-fill'],
        'staff' => ['Staff', 'bi-shield-lock-fill'],
        'lead' => ['Support Inbox', 'bi-headset'],
        'announcement' => ['Announcements', 'bi-bell-fill'],
        'impersonation' => ['Impersonation', 'bi-incognito'],
        'super-admin' => ['Sign-ins', 'bi-shield-lock-fill'],
    ];

    /** Property keys that are never shown, whoever is looking. */
    private const HIDDEN_KEY_PATTERN = '/password|token|secret|api_key/i';

    protected string $paginationTheme = 'bootstrap';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** "" = anyone, "system" = no staff member, otherwise a super admin id. */
    #[Url(as: 'by', except: '')]
    public string $actorFilter = '';

    #[Url(as: 'type', except: '')]
    public string $typeFilter = '';

    #[Url(as: 'from', except: '')]
    public string $dateFrom = '';

    #[Url(as: 'to', except: '')]
    public string $dateTo = '';

    public ?int $detailId = null;

    // ---------------------------------------------------------------- data

    private function filteredQuery(): Builder
    {
        $search = trim($this->search);
        $from = $this->parseDate($this->dateFrom);
        $to = $this->parseDate($this->dateTo);

        return Activity::query()
            ->when($search !== '', function (Builder $q) use ($search) {
                $like = '%'.$search.'%';
                // `properties` is a JSON column; LIKE on it works on MySQL and SQLite.
                $q->where(fn (Builder $q) => $q->where('description', 'like', $like)->orWhere('properties', 'like', $like));
            })
            ->when($this->actorFilter === 'system', fn (Builder $q) => $q->whereNull('causer_id'))
            ->when(ctype_digit($this->actorFilter), fn (Builder $q) => $q
                ->where('causer_type', SuperAdmin::class)
                ->where('causer_id', (int) $this->actorFilter))
            ->when($this->typeFilter !== '', fn (Builder $q) => $q->where('log_name', $this->typeFilter))
            ->when($from, fn (Builder $q) => $q->where('created_at', '>=', $from->startOfDay()))
            ->when($to, fn (Builder $q) => $q->where('created_at', '<=', $to->endOfDay()));
    }

    #[Computed]
    public function entries(): LengthAwarePaginator
    {
        return $this->filteredQuery()
            ->with('causer')
            ->latest('id')
            ->paginate(25);
    }

    #[Computed]
    public function admins(): Collection
    {
        return SuperAdmin::query()->orderBy('name')->get(['id', 'name']);
    }

    /** @return Collection<string, string> log_name => label, for the type filter */
    #[Computed]
    public function types(): Collection
    {
        return Activity::query()
            ->whereNotNull('log_name')
            ->distinct()
            ->pluck('log_name')
            ->mapWithKeys(fn (string $name) => [$name => $this->typeLabel($name)])
            ->sort();
    }

    #[Computed]
    public function selected(): ?Activity
    {
        return $this->detailId ? Activity::query()->with('causer')->find($this->detailId) : null;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingActorFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'actorFilter', 'typeFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    private function parseDate(string $value): ?Carbon
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    // ------------------------------------------------------------ presenters

    public function typeLabel(?string $logName): string
    {
        return self::TYPES[$logName][0] ?? Str::headline($logName ?? 'General');
    }

    public function typeIcon(?string $logName): string
    {
        return self::TYPES[$logName][1] ?? 'bi-terminal-fill';
    }

    /** Who did it: a staff name, "System" when nobody was signed in, or a fallback for removed accounts. */
    public function actorName(Activity $activity): string
    {
        if ($activity->causer_id === null) {
            return 'System';
        }

        return $activity->causer?->name ?? 'Removed account';
    }

    /** The thing the action was about, taken from what the action itself recorded. */
    public function subjectLabel(Activity $activity): ?string
    {
        $props = $activity->properties;

        foreach (['code', 'key', 'invoice_number', 'workspace', 'name', 'email'] as $field) {
            $value = $props->get($field);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * Details for the modal as label => text. Secrets are dropped; IP addresses are
     * only shown to super admins.
     *
     * @return array<string, string>
     */
    public function detailRows(Activity $activity): array
    {
        $rows = [];

        foreach ($activity->properties->toArray() as $key => $value) {
            if (preg_match(self::HIDDEN_KEY_PATTERN, (string) $key) === 1) {
                continue;
            }
            if (in_array($key, ['ip', 'ip_address'], true) && ! $this->canSeeIp()) {
                continue;
            }

            $rows[Str::headline((string) $key)] = match (true) {
                $value === null => '—',
                is_bool($value) => $value ? 'Yes' : 'No',
                is_scalar($value) => (string) $value,
                default => json_encode($this->withoutSecrets($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            };
        }

        return $rows;
    }

    private function withoutSecrets(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $clean = [];

        foreach ($value as $key => $item) {
            if (is_string($key) && preg_match(self::HIDDEN_KEY_PATTERN, $key) === 1) {
                continue;
            }
            $clean[$key] = $this->withoutSecrets($item);
        }

        return $clean;
    }

    // ---------------------------------------------------------- permissions

    public function canSeeIp(): bool
    {
        return $this->admin()->role === SuperAdminRole::SuperAdmin;
    }

    private function admin(): SuperAdmin
    {
        /** @var SuperAdmin */
        return Auth::guard('super_admin')->user();
    }

    // -------------------------------------------------------------- modals

    public function openDetail(int $id): void
    {
        $this->detailId = Activity::query()->findOrFail($id)->id;
        unset($this->selected);
    }

    public function closeDetail(): void
    {
        $this->detailId = null;
        unset($this->selected);
    }

    public function render()
    {
        return view('livewire.super-admin.activity-log');
    }
}
