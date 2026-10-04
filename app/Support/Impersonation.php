<?php

namespace App\Support;

use App\Models\ImpersonationLog;
use App\Models\User;
use App\Models\Workspace;
use App\Services\SuperAdmin\ImpersonationService;
use Illuminate\Support\Facades\Auth;

/**
 * Session side of impersonation. The staff member stays signed in on the
 * `super_admin` guard; the impersonated user is signed in on the `web` guard.
 *
 * Use Impersonation::active() in the tenant app to block sensitive actions
 * (changing a password or email, rotating API keys, deleting the workspace).
 */
class Impersonation
{
    public const SESSION_KEY = 'impersonation';

    /** @return array{log_id:int, admin_id:int, started_at:int, user_name:string, workspace_name:string}|null */
    public static function data(): ?array
    {
        $data = session(self::SESSION_KEY);

        return is_array($data) && isset($data['log_id'], $data['admin_id'], $data['started_at']) ? $data : null;
    }

    public static function active(): bool
    {
        return self::data() !== null;
    }

    public static function expired(array $data): bool
    {
        return now()->timestamp - (int) $data['started_at'] > ImpersonationService::TTL_MINUTES * 60;
    }

    public static function minutesLeft(array $data): int
    {
        $left = ImpersonationService::TTL_MINUTES * 60 - (now()->timestamp - (int) $data['started_at']);

        return max(0, (int) ceil($left / 60));
    }

    /** Signs the user in on the web guard (no remember-me) and marks the session. */
    public static function begin(User $user, Workspace $workspace, ImpersonationLog $log, int $adminId): void
    {
        // login() regenerates the session id and keeps the super_admin guard data.
        Auth::guard('web')->login($user);

        session()->put([
            'current_workspace_id' => $workspace->id,
            self::SESSION_KEY => [
                'log_id' => $log->id,
                'admin_id' => $adminId,
                'started_at' => now()->timestamp,
                'user_name' => $user->name,
                'workspace_name' => $workspace->name,
            ],
        ]);
    }

    /**
     * Drops the impersonated user from this session without touching the staff login.
     * Deliberately not Guard::logout(): that would rotate the real user's remember-me token.
     */
    public static function forget(): void
    {
        $guard = Auth::guard('web');

        session()->forget([$guard->getName(), 'password_hash_web', 'current_workspace_id', self::SESSION_KEY]);
        $guard->forgetUser();
    }
}
