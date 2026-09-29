<?php

use Illuminate\Support\Facades\Route;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

Route::get('/login', function () {
    $user = User::where('email', 'amanda.prohaska@example.com')->first();

    if ($user) {
        Auth::login($user);
        session()->regenerate();

        return redirect('app/dashboard'); // অথবা যেখানে নিতে চাও
    }

    return 'User not found';
})->name('login');

// ---- Public marketing website ----
Route::get('/', fn () => view('site.home'))->name('home');
Route::get('/pricing', fn () => view('site.pricing'))->name('pricing');
// Route::get('/login', fn () => view('site.login'))->name('login');
Route::get('/register', fn () => view('site.register'))->name('register');

// ---- Tenant app panel (workspace-scoped, needs auth + EnsureBelongsToWorkspace) ----
Route::middleware(['auth', 'workspace'])->prefix('app')->name('app.')->group(function () {
    Route::get('/dashboard', \App\Livewire\App\Dashboard::class)->name('dashboard');
    Route::get('/inbox', \App\Livewire\App\Inbox::class)->name('inbox');
    Route::get('/lyro ', \App\Livewire\App\Lyro::class)->name('lyro');
    Route::get('/flows', \App\Livewire\App\Flows::class)->name('flows');
    Route::get('/customers', \App\Livewire\App\Customers::class)->name('customers');
    Route::get('/analytics', \App\Livewire\App\Analytics::class)->name('analytics');

    Route::get('/settings', \App\Livewire\App\SettingsAccount::class)->name('settings');
    Route::get('/settings/account', \App\Livewire\App\SettingsAccount::class)->name('settings.account');
    Route::get('/settings/team', \App\Livewire\App\SettingsTeam::class)->name('settings.team');
    Route::get('/settings/macros', \App\Livewire\App\SettingsMacros::class)->name('settings.macros');
    Route::get('/settings/workflows', \App\Livewire\App\SettingsWorkflows::class)->name('settings.workflows');
    Route::get('/settings/tags', \App\Livewire\App\SettingsTags::class)->name('settings.tags');
    Route::get('/settings/sla', \App\Livewire\App\SettingsSla::class)->name('settings.sla');
    Route::get('/settings/csat', \App\Livewire\App\SettingsCsat::class)->name('settings.csat');
    Route::get('/settings/fields', \App\Livewire\App\SettingsFields::class)->name('settings.fields');
    Route::get('/settings/tracking', \App\Livewire\App\SettingsTracking::class)->name('settings.tracking');
    Route::get('/settings/developer', \App\Livewire\App\SettingsDeveloper::class)->name('settings.developer');

    Route::get('/settings/appearance', \App\Livewire\App\SettingsAppearance::class)->name('settings.appearance');
    Route::get('/settings/chat-page', \App\Livewire\App\SettingsChatPage::class)->name('settings.chat-page');
    Route::get('/settings/translations', \App\Livewire\App\SettingsTranslations::class)->name('settings.translations');
    Route::get('/settings/installation', \App\Livewire\App\SettingsInstallation::class)->name('settings.installation');
    Route::get('/settings/email', \App\Livewire\App\SettingsEmail::class)->name('settings.email');
    Route::get('/settings/social/{type}', \App\Livewire\App\SettingsSocialChannel::class)->whereIn('type', ['messenger', 'instagram', 'whatsapp'])->name('settings.social');
    
    Route::get('/settings/billing', \App\Livewire\App\SettingsBilling::class)->name('settings.billing');
    Route::get('/settings/usage', \App\Livewire\App\SettingsUsage::class)->name('settings.usage');
    Route::get('/settings/preferences', \App\Livewire\App\SettingsPreferences::class)->name('settings.preferences');
    Route::get('/settings/operating-hours', \App\Livewire\App\SettingsOperatingHours::class)->name('settings.operating-hours');
    Route::get('/settings/notifications', \App\Livewire\App\SettingsNotifications::class)->name('settings.notifications');
    Route::get('/settings/download-app', \App\Livewire\App\SettingsDownloadApp::class)->name('settings.download-app');
});

// ---- Super-admin (platform staff only) ----
Route::middleware(['auth:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', fn () => view('super-admin.dashboard'))->name('dashboard');
    Route::get('/tenants', fn () => view('super-admin.tenants'))->name('tenants');
    Route::get('/billing', fn () => view('super-admin.billing'))->name('billing');
});