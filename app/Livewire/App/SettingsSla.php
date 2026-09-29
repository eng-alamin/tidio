<?php

namespace App\Livewire\App;

use App\Models\Sla;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SettingsSla extends Component
{
    public int $first_response_minutes = 60;
    public int $resolution_minutes = 1440;
    public string $applies_to = 'ticket';

    protected function rules(): array
    {
        return [
            'first_response_minutes' => 'required|integer|min:1',
            'resolution_minutes' => 'required|integer|min:1',
            'applies_to' => ['required', Rule::in(['ticket', 'chat', 'all'])],
        ];
    }

    private function currentSla(): ?Sla
    {
        return Sla::where('workspace_id', app('currentWorkspace')->id)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    public function mount(): void
    {
        $sla = $this->currentSla();

        if (! $sla) {
            return;
        }

        $this->first_response_minutes = (int) $sla->first_response_minutes;
        $this->resolution_minutes = (int) $sla->resolution_minutes;
        $this->applies_to = $this->appliesToFrom($sla->conditions);
    }

    private function appliesToFrom(?array $conditions): string
    {
        foreach ($conditions ?? [] as $condition) {
            if (($condition['field'] ?? null) === 'type'
                && ($condition['op'] ?? '=') === '='
                && in_array($condition['value'] ?? null, ['ticket', 'chat'], true)) {
                return $condition['value'];
            }
        }

        return 'all';
    }

    public function save(): void
    {
        $this->validate();

        $sla = $this->currentSla();

        $conditions = collect($sla?->conditions ?? [])
            ->reject(fn ($c) => ($c['field'] ?? null) === 'type')
            ->values();

        if ($this->applies_to !== 'all') {
            $conditions->push(['field' => 'type', 'op' => '=', 'value' => $this->applies_to]);
        }

        $payload = [
            'first_response_minutes' => $this->first_response_minutes,
            'resolution_minutes' => $this->resolution_minutes,
            'conditions' => $conditions->isEmpty() ? null : $conditions->all(),
        ];

        if ($sla) {
            $sla->update($payload);
        } else {
            Sla::create($payload + [
                'workspace_id' => app('currentWorkspace')->id,
                'name' => 'Default SLA', // required column
                'is_default' => true,
            ]);
        }

        $this->dispatch('toast', message: 'SLA policy saved.');
    }

    public function render()
    {
        return view('livewire.app.settings-sla')
            ->layout('layouts.app', ['title' => 'Settings']);
    }
}