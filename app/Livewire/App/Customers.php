<?php

namespace App\Livewire\App;

use App\Models\Contact;
use App\Models\Tag;
use App\Models\Visitor;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class Customers extends Component
{
    use WithFileUploads;
    public string $tab = 'contacts';

    public string $search = '';

    public ?int $tagFilter = null;

    public bool $showAdd = false;

    public bool $showImport = false;

    public $csvFile = null;

    public ?array $importResult = null;

    public ?string $importError = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|max:50')]
    public string $phone = '';

    #[Validate('nullable|string|max:100')]
    public string $country = '';

    /** @var array<int> */
    public array $selectedTags = [];

    public function setTab(string $tab): void
    {
        $this->tab = $tab === 'visitors' ? 'visitors' : 'contacts';
    }

    #[Computed]
    public function tags(): Collection
    {
        return Tag::where('workspace_id', app('currentWorkspace')->id)->orderBy('name')->get();
    }

    #[Computed]
    public function contacts(): Collection
    {
        return Contact::query()
            ->where('workspace_id', app('currentWorkspace')->id)
            ->when($this->search, fn ($q) => $q
                ->where(fn ($q2) => $q2
                    ->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%')))
            ->when($this->tagFilter, fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('tags.id', $this->tagFilter)))
            ->with(['tags', 'visitors' => fn ($q) => $q->latest('last_seen_at')->limit(1)])
            ->latest()
            ->get();
    }

    #[Computed]
    public function visitors(): Collection
    {
        return Visitor::query()
            ->where('workspace_id', app('currentWorkspace')->id)
            ->orderByDesc('last_seen_at')
            ->limit(50)
            ->get();
    }

    public function toggleTag(int $tagId): void
    {
        if (in_array($tagId, $this->selectedTags, true)) {
            $this->selectedTags = array_values(array_diff($this->selectedTags, [$tagId]));
        } else {
            $this->selectedTags[] = $tagId;
        }
    }

    public function startImport(): void
    {
        $this->reset(['csvFile', 'importResult', 'importError']);
        $this->showImport = true;
    }

    public function cancelImport(): void
    {
        $this->reset(['csvFile', 'importResult', 'importError', 'showImport']);
    }

    public function importCsv(): void
    {
        $this->validate(['csvFile' => 'required|file|max:2048']);
        $this->importError = null;
        $this->importResult = null;

        $extension = strtolower($this->csvFile->getClientOriginalExtension());

        if (! in_array($extension, ['csv', 'txt'], true)) {
            $this->importError = 'Please upload a .csv (or .txt) file — got .'.$extension.'.';

            return;
        }

        $workspace = app('currentWorkspace');
        $handle = fopen($this->csvFile->getRealPath(), 'r');

        if (! $handle) {
            $this->importError = 'Could not read that file.';

            return;
        }

        $header = fgetcsv($handle);

        if (! $header) {
            fclose($handle);
            $this->importError = 'That file looks empty.';

            return;
        }

        // Windows/Excel often save CSVs with a UTF-8 BOM, which would otherwise
        // make the first header cell mismatch (e.g. "\uFEFFname" != "name").
        $header = array_map(fn ($h) => strtolower(trim((string) preg_replace('/^\x{FEFF}/u', '', (string) $h))), $header);
        $colName = array_search('name', $header, true);
        $colEmail = array_search('email', $header, true);
        $colPhone = array_search('phone', $header, true);
        $colCountry = array_search('country', $header, true);
        $colTags = array_search('tags', $header, true);

        if ($colName === false && $colEmail === false) {
            fclose($handle);
            $this->importError = 'CSV needs at least a "name" or "email" column. Found columns: '.implode(', ', $header);

            return;
        }

        $added = 0;
        $skipped = 0;
        $tagCache = [];

        while (($row = fgetcsv($handle)) !== false) {
            $name = $colName !== false ? trim((string) ($row[$colName] ?? '')) : '';
            $email = $colEmail !== false ? trim((string) ($row[$colEmail] ?? '')) : '';

            if ($name === '' && $email === '') {
                continue;
            }

            if ($email !== '' && $workspace->contacts()->where('email', $email)->exists()) {
                $skipped++;

                continue;
            }

            $contact = Contact::create([
                'workspace_id' => $workspace->id,
                'name' => $name ?: null,
                'email' => $email ?: null,
                'phone' => $colPhone !== false ? (trim((string) ($row[$colPhone] ?? '')) ?: null) : null,
                'country' => $colCountry !== false ? (trim((string) ($row[$colCountry] ?? '')) ?: null) : null,
                'source' => 'import',
            ]);

            if ($colTags !== false && trim((string) ($row[$colTags] ?? '')) !== '') {
                $tagIds = [];

                foreach (explode(',', $row[$colTags]) as $tagName) {
                    $tagName = trim($tagName);

                    if ($tagName === '') {
                        continue;
                    }

                    $key = strtolower($tagName);

                    if (! isset($tagCache[$key])) {
                        $tagCache[$key] = Tag::firstOrCreate(
                            ['workspace_id' => $workspace->id, 'name' => $tagName],
                            ['color' => '#2B5FE2']
                        );
                    }

                    $tagIds[] = $tagCache[$key]->id;
                }

                if ($tagIds) {
                    $contact->tags()->attach($tagIds);
                }
            }

            $added++;
        }

        fclose($handle);

        $this->importResult = ['added' => $added, 'skipped' => $skipped];
        $this->reset('csvFile');
        unset($this->contacts, $this->tags);

        $this->dispatch('toast', message: "Imported {$added} contact(s)".($skipped ? ", skipped {$skipped} duplicate(s)." : '.'));
    }

    public function addContact(): void
    {
        $this->validate();

        $contact = Contact::create([
            'workspace_id' => app('currentWorkspace')->id,
            'name' => $this->name,
            'email' => $this->email ?: null,
            'phone' => $this->phone ?: null,
            'country' => $this->country ?: null,
            'source' => 'manual',
        ]);

        if (! empty($this->selectedTags)) {
            $contact->tags()->attach($this->selectedTags);
        }

        $this->reset(['name', 'email', 'phone', 'country', 'selectedTags', 'showAdd']);
        unset($this->contacts);

        $this->dispatch('toast', message: 'Contact added.');
    }

    public function deleteContact(int $contactId): void
    {
        Contact::where('workspace_id', app('currentWorkspace')->id)->where('id', $contactId)->delete();
        unset($this->contacts);
    }

    public function render()
    {
        return view('livewire.app.customers')
            ->layout('layouts.app', ['title' => 'Customers']);
    }
}