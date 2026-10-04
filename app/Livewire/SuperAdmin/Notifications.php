<?php

namespace App\Livewire\SuperAdmin;

use App\Enums\AnnouncementAudience;
use App\Enums\SuperAdminRole;
use App\Models\Announcement;
use App\Models\SuperAdmin;
use App\Services\SuperAdmin\AnnouncementService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
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
#[Title('Notifications')]
class Notifications extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** live | scheduled | ended | inactive */
    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    /** null | form | delete */
    public ?string $modal = null;

    public ?int $editingId = null;

    public string $title = '';

    public string $body = '';

    public string $audience = 'all';

    public string $startsAt = '';

    public string $endsAt = '';

    public bool $isActive = true;

    // ---------------------------------------------------------------- data

    /**
     * Status is derived, in this order: inactive, ended, scheduled, live.
     * Keep this in sync with status() below.
     */
    private function applyStatus(Builder $q, string $status): void
    {
        $now = now();

        switch ($status) {
            case 'inactive':
                $q->where('is_active', false);
                break;
            case 'ended':
                $q->where('is_active', true)->where('ends_at', '<', $now);
                break;
            case 'scheduled':
                $q->where('is_active', true)
                    ->where(fn ($w) => $w->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
                    ->where('starts_at', '>', $now);
                break;
            case 'live':
                $q->where('is_active', true)
                    ->where(fn ($w) => $w->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
                    ->where(fn ($w) => $w->whereNull('starts_at')->orWhere('starts_at', '<=', $now));
                break;
        }
    }

    /** @return array{live:int, scheduled:int, ended:int, inactive:int} */
    #[Computed]
    public function stats(): array
    {
        $count = fn (string $status) => tap(Announcement::query(), fn ($q) => $this->applyStatus($q, $status))->count();

        return [
            'live' => $count('live'),
            'scheduled' => $count('scheduled'),
            'ended' => $count('ended'),
            'inactive' => $count('inactive'),
        ];
    }

    #[Computed]
    public function announcements(): LengthAwarePaginator
    {
        $search = trim($this->search);

        return Announcement::query()
            ->with('creator:id,name')
            ->when($search !== '', fn (Builder $q) => $q->where('title', 'like', '%'.$search.'%'))
            ->when($this->statusFilter !== '', fn (Builder $q) => $this->applyStatus($q, $this->statusFilter))
            ->latest('id')
            ->paginate(10);
    }

    #[Computed]
    public function editing(): ?Announcement
    {
        return $this->editingId ? Announcement::query()->find($this->editingId) : null;
    }

    public function mount(): void
    {
        if (! in_array($this->statusFilter, ['', 'live', 'scheduled', 'ended', 'inactive'], true)) {
            $this->statusFilter = '';
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    // ------------------------------------------------------------ presenters

    /** @return array{label:string, class:string} */
    public function status(Announcement $a): array
    {
        if (! $a->is_active) {
            return ['label' => 'Off', 'class' => 'status-suspended'];
        }
        if ($a->ends_at && $a->ends_at->isPast()) {
            return ['label' => 'Ended', 'class' => 'status-past'];
        }
        if ($a->starts_at && $a->starts_at->isFuture()) {
            return ['label' => 'Scheduled', 'class' => 'status-trial'];
        }

        return ['label' => 'Live', 'class' => 'status-active'];
    }

    public function audienceLabel(AnnouncementAudience $audience): string
    {
        return match ($audience) {
            AnnouncementAudience::All => 'Everyone',
            AnnouncementAudience::WorkspaceOwners => 'Workspace owners',
            AnnouncementAudience::TrialUsers => 'Trial users',
        };
    }

    public function windowLabel(Announcement $a): string
    {
        $from = $a->starts_at?->format('Y-m-d H:i');
        $until = $a->ends_at?->format('Y-m-d H:i');

        return match (true) {
            $from && $until => "{$from} to {$until}",
            $until !== null => "Until {$until}",
            $from !== null => "From {$from}",
            default => 'Always',
        };
    }

    // ---------------------------------------------------------- permissions

    /** Announcements reach tenants, so only super admins change them. */
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

        $a = Announcement::findOrFail($id);

        $this->resetForm();
        $this->editingId = $a->id;
        $this->title = $a->title;
        $this->body = $a->body;
        $this->audience = $a->audience->value;
        $this->startsAt = $a->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->endsAt = $a->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->isActive = $a->is_active;
        $this->modal = 'form';
    }

    public function confirmDelete(int $id): void
    {
        abort_unless($this->canManage(), 403);

        $this->resetForm();
        $this->editingId = Announcement::findOrFail($id)->id;
        $this->modal = 'delete';
    }

    public function closeModal(): void
    {
        $this->modal = null;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'title', 'body', 'startsAt', 'endsAt']);
        $this->audience = 'all';
        $this->isActive = true;
        $this->resetValidation();
        unset($this->editing);
    }

    // ------------------------------------------------------------- actions

    public function save(AnnouncementService $service): void
    {
        abort_unless($this->canManage(), 403);
        abort_unless($this->modal === 'form', 422);

        $this->title = trim($this->title);
        $this->body = trim($this->body);

        $endsRules = ['nullable', 'date'];
        if ($this->startsAt !== '') {
            $endsRules[] = 'after:startsAt';
        }

        $this->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:2000'],
            'audience' => ['required', Rule::enum(AnnouncementAudience::class)],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => $endsRules,
            'isActive' => ['boolean'],
        ], [
            'endsAt.after' => 'The end must be after the start.',
        ]);

        $payload = [
            'title' => $this->title,
            'body' => $this->body,
            'audience' => $this->audience,
            'starts_at' => $this->startsAt !== '' ? Carbon::parse($this->startsAt) : null,
            'ends_at' => $this->endsAt !== '' ? Carbon::parse($this->endsAt) : null,
            'is_active' => $this->isActive,
        ];

        $editing = $this->editingId !== null;

        try {
            $editing
                ? $service->update($this->admin(), $this->editingId, $payload)
                : $service->create($this->admin(), $payload);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
            $this->closeModal();

            return;
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not save the announcement. Please try again.', type: 'error');

            return;
        }

        if (! $editing) {
            $this->resetPage();
        }

        $this->dispatch('toast', message: $editing ? 'Announcement updated.' : 'Announcement created.', type: 'ok');
        $this->closeModal();
    }

    public function toggleActive(int $id, AnnouncementService $service): void
    {
        abort_unless($this->canManage(), 403);

        $a = Announcement::findOrFail($id);
        $makeActive = ! $a->is_active;

        try {
            $service->setActive($this->admin(), $a->id, $makeActive);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');

            return;
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not update the announcement.', type: 'error');

            return;
        }

        $this->dispatch('toast', message: $makeActive ? 'Announcement switched on.' : 'Announcement switched off.', type: $makeActive ? 'ok' : 'warn');
    }

    public function delete(AnnouncementService $service): void
    {
        abort_unless($this->canManage(), 403);
        abort_unless($this->modal === 'delete' && $this->editingId !== null, 422);

        try {
            $service->delete($this->admin(), $this->editingId);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
            $this->closeModal();

            return;
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not delete the announcement.', type: 'error');

            return;
        }

        $this->dispatch('toast', message: 'Announcement deleted.', type: 'warn');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.super-admin.notifications');
    }
}
