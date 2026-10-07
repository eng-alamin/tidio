<?php

namespace App\Livewire\SuperAdmin;

use App\Enums\SuperAdminRole;
use App\Models\SuperAdmin;
use App\Services\SuperAdmin\PlatformSettingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('layouts.super-admin')]
#[Title('Settings')]
class Settings extends Component
{
    public string $platformName = '';

    public string $supportEmail = '';

    public string $defaultTimezone = 'UTC';

    /** int|string because an emptied number input arrives as an empty string; validation rejects it. */
    public int|string $trialDays = 14;

    public bool $allowImpersonation = true;

    public bool $maintenanceMode = false;

    public string $maintenanceMessage = '';

    public function mount(PlatformSettingsService $settings): void
    {
        abort_unless($this->canManage(), 403);

        $this->fillFrom($settings->all());
    }

    /** Platform settings affect every tenant, so only super admins can open or change them. */
    public function canManage(): bool
    {
        return $this->admin()->role === SuperAdminRole::SuperAdmin;
    }

    /** @return list<string> */
    public function timezones(): array
    {
        return timezone_identifiers_list();
    }

    public function saveGeneral(PlatformSettingsService $settings): void
    {
        abort_unless($this->canManage(), 403);

        $this->validate([
            'platformName' => ['required', 'string', 'max:60'],
            'supportEmail' => ['required', 'string', 'email', 'max:190'],
            'defaultTimezone' => ['required', 'string', Rule::in($this->timezones())],
            'trialDays' => ['required', 'integer', 'between:1,90'],
        ]);

        $this->persist($settings, [
            'platform_name' => trim($this->platformName),
            'support_email' => trim($this->supportEmail),
            'default_timezone' => $this->defaultTimezone,
            'trial_days' => (int) $this->trialDays,
        ], 'General settings saved.');
    }

    public function saveAccess(PlatformSettingsService $settings): void
    {
        abort_unless($this->canManage(), 403);

        $this->validate([
            'allowImpersonation' => ['boolean'],
            'maintenanceMode' => ['boolean'],
            'maintenanceMessage' => ['required', 'string', 'max:255'],
        ]);

        $this->persist($settings, [
            'allow_impersonation' => $this->allowImpersonation,
            'maintenance_mode' => $this->maintenanceMode,
            'maintenance_message' => trim($this->maintenanceMessage),
        ], 'Access settings saved.');
    }

    /** @param array<string, string|int|bool> $data */
    private function persist(PlatformSettingsService $settings, array $data, string $success): void
    {
        try {
            $changed = $settings->update($this->admin(), $data);
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not save the settings. Please try again.', type: 'error');

            return;
        }

        $this->fillFrom($settings->all());
        $this->dispatch('toast', message: $changed === [] ? 'Nothing to save, no changes.' : $success, type: 'ok');
    }

    /** @param array<string, string|int|bool> $values */
    private function fillFrom(array $values): void
    {
        $this->platformName = (string) $values['platform_name'];
        $this->supportEmail = (string) $values['support_email'];
        $this->defaultTimezone = (string) $values['default_timezone'];
        $this->trialDays = (int) $values['trial_days'];
        $this->allowImpersonation = (bool) $values['allow_impersonation'];
        $this->maintenanceMode = (bool) $values['maintenance_mode'];
        $this->maintenanceMessage = (string) $values['maintenance_message'];
    }

    private function admin(): SuperAdmin
    {
        /** @var SuperAdmin */
        return Auth::guard('super_admin')->user();
    }

    public function render()
    {
        return view('livewire.super-admin.settings');
    }
}
