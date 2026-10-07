<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }

        if (Auth::user()->is_disabled) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'This account has been disabled. Please contact support.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('app.dashboard'));
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'company_name' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:8'],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'Please accept the terms to continue.',
        ]);

        $workspace = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
                'password' => $data['password'], // hashed by the model cast
            ]);

            $workspace = Workspace::create([
                'owner_id' => $user->id,
                'name' => $data['company_name'],
                'slug' => Str::slug($data['company_name']).'-'.Str::lower(Str::random(5)),
                'plan' => 'free',
                'trial_ends_at' => now()->addDays(14),
            ]);

            $owner = Role::create([
                'workspace_id' => $workspace->id,
                'name' => 'Owner',
                'permissions' => ['*'],
                'is_system' => true,
            ]);

            $user->workspaces()->attach($workspace->id, [
                'role_id' => $owner->id,
                'status' => 'active',
                'joined_at' => now(),
            ]);

            Auth::login($user);

            return $workspace;
        });

        $request->session()->regenerate();
        $request->session()->put('current_workspace_id', $workspace->id);

        return redirect()->route('app.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}