<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SuperAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    private const GUARD = 'super_admin';

    private const MAX_ATTEMPTS = 5;

    public function showLogin(): View
    {
        return view('super-admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Try again in {$seconds} seconds.",
            ]);
        }

        if (! Auth::guard(self::GUARD)->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        /** @var SuperAdmin $admin */
        $admin = Auth::guard(self::GUARD)->user();

        activity('super-admin')
            ->causedBy($admin)
            ->withProperties(['ip' => $request->ip()])
            ->log('Super admin signed in');

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $admin = Auth::guard(self::GUARD)->user();

        if ($admin) {
            activity('super-admin')
                ->causedBy($admin)
                ->withProperties(['ip' => $request->ip()])
                ->log('Super admin signed out');
        }

        // Only drop the super-admin guard; do not invalidate the whole session,
        // so a tenant session in the same browser stays untouched.
        Auth::guard(self::GUARD)->logout();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
