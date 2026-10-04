<?php

namespace App\Services\SuperAdmin;

use App\Enums\SuperAdminRole;
use App\Models\ImpersonationLog;
use App\Models\SuperAdmin;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Starts and ends "log in as this user" sessions for platform staff.
 * Every session is written to impersonation_logs and to the activity log.
 */
class ImpersonationService
{
    /** An impersonation session stops working after this many minutes. */
    public const TTL_MINUTES = 60;

    /** Super admins and support staff may impersonate. Billing admins may not. */
    public static function canImpersonate(SuperAdmin $admin): bool
    {
        return in_array($admin->role, [SuperAdminRole::SuperAdmin, SuperAdminRole::SupportStaff], true);
    }

    /**
     * Records the start of a session. The caller signs the user in afterwards.
     *
     * @throws RuntimeException when the staff member, user or workspace is not eligible
     * @throws Throwable
     */
    public function start(SuperAdmin $actor, User $user, Workspace $workspace, ?string $ip): ImpersonationLog
    {
        if (! self::canImpersonate($actor)) {
            throw new RuntimeException("Your role can't impersonate users.");
        }
        if ($user->is_disabled) {
            throw new RuntimeException('This account is disabled. Enable it before impersonating.');
        }
        if ($workspace->is_suspended) {
            throw new RuntimeException('This workspace is suspended. Reactivate it before impersonating.');
        }

        $isMember = $workspace->users()
            ->where('users.id', $user->id)
            ->wherePivot('status', 'active')
            ->exists();

        if (! $isMember) {
            throw new RuntimeException('This user is not an active member of that workspace.');
        }

        return DB::transaction(function () use ($actor, $user, $workspace, $ip) {
            $this->closeStaleLogs($actor);

            $log = ImpersonationLog::create([
                'super_admin_id' => $actor->id,
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'ip_address' => $ip,
                'started_at' => now(),
            ]);

            activity('impersonation')
                ->causedBy($actor)
                ->performedOn($user)
                ->withProperties([
                    'log_id' => $log->id,
                    'workspace_id' => $workspace->id,
                    'workspace' => $workspace->name,
                    'ip' => $ip,
                ])
                ->log('Impersonation started');

            return $log;
        });
    }

    /**
     * Closes a session. Safe to call twice: only the first call writes.
     *
     * @param  string  $reason  stopped | switched | expired | admin_signed_out
     */
    public function end(?SuperAdmin $actor, int $logId, string $reason = 'stopped'): void
    {
        $closed = ImpersonationLog::query()
            ->whereKey($logId)
            ->whereNull('ended_at')
            ->update(['ended_at' => now()]);

        if ($closed === 0) {
            return;
        }

        $log = ImpersonationLog::query()->find($logId);
        $user = $log?->user()->withTrashed()->first();

        $entry = activity('impersonation')
            ->causedBy($actor ?? $log?->superAdmin)
            ->withProperties([
                'log_id' => $logId,
                'reason' => $reason,
                'seconds' => $log?->started_at ? (int) $log->started_at->diffInSeconds(now()) : null,
            ]);

        if ($user) {
            $entry->performedOn($user);
        }

        $entry->log('Impersonation ended');
    }

    /** Sessions the browser never closed (tab shut, cookie lost) are ended at their expiry time. */
    private function closeStaleLogs(SuperAdmin $actor): void
    {
        ImpersonationLog::query()
            ->where('super_admin_id', $actor->id)
            ->whereNull('ended_at')
            ->where('started_at', '<', now()->subMinutes(self::TTL_MINUTES))
            ->get()
            ->each(fn (ImpersonationLog $log) => $log->update([
                'ended_at' => $log->started_at->copy()->addMinutes(self::TTL_MINUTES),
            ]));
    }
}
