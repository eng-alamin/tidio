<?php

namespace App\Livewire\SuperAdmin;

use App\Enums\SuperAdminRole;
use App\Models\Integration;
use App\Models\SuperAdmin;
use App\Services\SuperAdmin\IntegrationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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
#[Title('Integrations')]
class Integrations extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'category', except: '')]
    public string $categoryFilter = '';

    /** '' | featured | standard */
    #[Url(as: 'show', except: '')]
    public string $featuredFilter = '';

    /** null | form | delete */
    public ?string $modal = null;

    public ?int $editingId = null;

    public string $name = '';

    public string $slug = '';

    public string $category = '';

    public string $logo = '';

    public string $description = '';

    public bool $isFeatured = false;

    // ---------------------------------------------------------------- data

    /** @return array{total:int, featured:int, categories:int} */
    #[Computed]
    public function stats(): array
    {
        return [
            'total' => Integration::query()->count(),
            'featured' => Integration::query()->where('is_featured', true)->count(),
            'categories' => Integration::query()->whereNotNull('category')->where('category', '!=', '')->distinct()->count('category'),
        ];
    }

    /** @return Collection<int, string> */
    #[Computed]
    public function categories(): Collection
    {
        return Integration::query()->whereNotNull('category')->where('category', '!=', '')
            ->distinct()->orderBy('category')->pluck('category');
    }

    #[Computed]
    public function integrations(): LengthAwarePaginator
    {
        $search = trim($this->search);

        return Integration::query()
            ->when($search !== '', fn (Builder $q) => $q->where(function (Builder $w) use ($search) {
                $w->where('name', 'like', '%'.$search.'%')->orWhere('slug', 'like', '%'.$search.'%');
            }))
            ->when($this->categoryFilter !== '', fn (Builder $q) => $q->where('category', $this->categoryFilter))
            ->when($this->featuredFilter === 'featured', fn (Builder $q) => $q->where('is_featured', true))
            ->when($this->featuredFilter === 'standard', fn (Builder $q) => $q->where('is_featured', false))
            ->orderBy('name')
            ->paginate(10);
    }

    #[Computed]
    public function editing(): ?Integration
    {
        return $this->editingId ? Integration::query()->find($this->editingId) : null;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingFeaturedFilter(): void
    {
        $this->resetPage();
    }

    /** Suggest a slug from the name until the admin edits the slug themselves. New integrations only. */
    public function updatedName(): void
    {
        if ($this->editingId === null && $this->slugTouched === false) {
            $this->slug = Str::slug($this->name);
        }
    }

    public bool $slugTouched = false;

    public function updatedSlug(): void
    {
        $this->slugTouched = true;
    }

    // ---------------------------------------------------------- permissions

    /** Catalogue content is public-facing, so only super admins change it. */
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

        $integration = Integration::findOrFail($id);

        $this->resetForm();
        $this->editingId = $integration->id;
        $this->name = $integration->name;
        $this->slug = $integration->slug;
        $this->category = (string) $integration->category;
        $this->logo = (string) $integration->logo;
        $this->description = (string) $integration->description;
        $this->isFeatured = $integration->is_featured;
        $this->modal = 'form';
    }

    public function confirmDelete(int $id): void
    {
        abort_unless($this->canManage(), 403);

        $this->resetForm();
        $this->editingId = Integration::findOrFail($id)->id;
        $this->modal = 'delete';
    }

    public function closeModal(): void
    {
        $this->modal = null;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'slug', 'category', 'logo', 'description', 'isFeatured', 'slugTouched']);
        $this->resetValidation();
        unset($this->editing);
    }

    // ------------------------------------------------------------- actions

    public function save(IntegrationService $service): void
    {
        abort_unless($this->canManage(), 403);

        $integration = $this->editingId !== null ? Integration::findOrFail($this->editingId) : null;

        // The slug is immutable once created: take it from the database, never the request.
        if ($integration) {
            $this->slug = $integration->slug;
        }

        $this->name = trim($this->name);
        $this->slug = Str::lower(trim($this->slug));
        $this->category = trim($this->category);
        $this->logo = trim($this->logo);
        $this->description = trim($this->description);

        $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                // Includes soft-deleted rows on purpose: the DB unique index does too.
                Rule::unique('integrations', 'slug')->ignore($integration?->id),
            ],
            'category' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'url:http,https', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'isFeatured' => ['boolean'],
        ], [
            'slug.regex' => 'Use lowercase letters, numbers and single dashes only.',
            'slug.unique' => 'That slug is taken. Deleted integrations keep their slug reserved.',
            'logo.url' => 'Enter a full web address starting with http:// or https://.',
        ]);

        $payload = [
            'name' => $this->name,
            'category' => $this->category !== '' ? $this->category : null,
            'logo' => $this->logo !== '' ? $this->logo : null,
            'description' => $this->description !== '' ? $this->description : null,
            'is_featured' => $this->isFeatured,
        ];

        try {
            if ($integration) {
                $service->update($this->admin(), $integration->id, $payload);
            } else {
                $service->create($this->admin(), $payload + ['slug' => $this->slug]);
            }
        } catch (UniqueConstraintViolationException) {
            $this->addError('slug', 'That slug is taken. Deleted integrations keep their slug reserved.');

            return;
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
            $this->closeModal();

            return;
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not save the integration. Please try again.', type: 'error');

            return;
        }

        if (! $integration) {
            $this->resetPage();
        }

        $this->dispatch('toast', message: $integration ? "{$this->name} updated." : "{$this->name} added.", type: 'ok');
        $this->closeModal();
    }

    public function toggleFeatured(int $id, IntegrationService $service): void
    {
        abort_unless($this->canManage(), 403);

        $integration = Integration::findOrFail($id);
        $featured = ! $integration->is_featured;

        try {
            $service->setFeatured($this->admin(), $integration->id, $featured);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');

            return;
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not update the integration.', type: 'error');

            return;
        }

        $this->dispatch('toast', message: $featured ? "{$integration->name} is now featured." : "{$integration->name} is no longer featured.", type: $featured ? 'ok' : 'warn');
    }

    public function delete(IntegrationService $service): void
    {
        abort_unless($this->canManage(), 403);
        abort_unless($this->modal === 'delete' && $this->editingId !== null, 422);

        $name = $this->editing?->name ?? 'Integration';

        try {
            $service->delete($this->admin(), $this->editingId);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
            $this->closeModal();

            return;
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not delete the integration.', type: 'error');

            return;
        }

        $this->dispatch('toast', message: "{$name} deleted.", type: 'warn');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.super-admin.integrations');
    }
}
