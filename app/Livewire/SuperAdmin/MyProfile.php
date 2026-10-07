<?php

namespace App\Livewire\SuperAdmin;

use App\Models\SuperAdmin;
use App\Services\SuperAdmin\ProfileService;
use App\Support\UserAgentSummary;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('layouts.super-admin')]
#[Title('My Profile')]
class MyProfile extends Component
{
    private const PASSWORD_ATTEMPTS = 5;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $timezone = '';

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public function mount(): void
    {
        $admin = $this->admin();

        $this->name = $admin->name;
        $this->email = $admin->email;
        $this->phone = (string) $admin->phone;
        $this->timezone = (string) $admin->timezone;
    }

    /** @return list<string> */
    public function timezones(): array
    {
        return timezone_identifiers_list();
    }

    /** @return array{browser:string, os:string, ip:?string} what this device looks like to the server */
    public function currentDevice(): array
    {
        $agent = request()->userAgent();

        return [
            'browser' => UserAgentSummary::browser($agent) ?? 'Unknown browser',
            'os' => UserAgentSummary::os($agent) ?? 'Unknown OS',
            'ip' => request()->ip(),
        ];
    }

    public function saveProfile(ProfileService $service): void
    {
        $admin = $this->admin();

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:190', Rule::unique('super_admins', 'email')->ignore($admin->id)],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+()\-\s.]*$/'],
            'timezone' => ['nullable', 'string', Rule::in($this->timezones())],
        ], [
            'phone.regex' => 'Use digits, spaces and + ( ) - . only.',
        ]);

        try {
            $changed = $service->updateProfile($admin, [
                'name' => trim($this->name),
                'email' => mb_strtolower(trim($this->email)),
                'phone' => trim($this->phone) !== '' ? trim($this->phone) : null,
                'timezone' => $this->timezone !== '' ? $this->timezone : null,
            ]);
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not update your profile. Please try again.', type: 'error');

            return;
        }

        $this->email = $admin->email;
        $this->dispatch('toast', message: $changed === [] ? 'No changes to save.' : 'Profile updated.', type: 'ok');
    }

    public function changePassword(ProfileService $service): void
    {
        $admin = $this->admin();
        $throttleKey = 'admin-password|'.$admin->id;

        $this->validate([
            'currentPassword' => ['required', 'string'],
            'newPassword' => ['required', 'string', Password::min(10)->mixedCase()->numbers(), 'different:currentPassword'],
            'newPasswordConfirmation' => ['required', 'same:newPassword'],
        ], [
            'newPassword.different' => 'The new password must be different from the current one.',
            'newPasswordConfirmation.same' => 'The confirmation does not match the new password.',
        ]);

        if (RateLimiter::tooManyAttempts($throttleKey, self::PASSWORD_ATTEMPTS)) {
            $this->addError('currentPassword', 'Too many attempts. Try again in '.RateLimiter::availableIn($throttleKey).' seconds.');

            return;
        }

        if (! Hash::check($this->currentPassword, $admin->password)) {
            RateLimiter::hit($throttleKey);
            $this->addError('currentPassword', 'The current password is not correct.');

            return;
        }

        try {
            $service->changePassword($admin, $this->newPassword);
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'Could not change your password. Please try again.', type: 'error');

            return;
        }

        RateLimiter::clear($throttleKey);
        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->dispatch('toast', message: 'Password updated.', type: 'ok');
    }

    private function admin(): SuperAdmin
    {
        /** @var SuperAdmin */
        return Auth::guard('super_admin')->user();
    }

    public function render()
    {
        return view('livewire.super-admin.my-profile', ['admin' => $this->admin()]);
    }
}
