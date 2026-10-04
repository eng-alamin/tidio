<?php

namespace App\Http\Middleware;

use App\Services\SuperAdmin\ImpersonationService;
use App\Support\Impersonation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs on every web request. Ends an impersonation session when it has expired,
 * or when the staff member who started it is no longer signed in or allowed to do it.
 */
class EnforceImpersonation
{
    public function handle(Request $request, Closure $next): Response
    {
        $data = Impersonation::data();

        if ($data === null) {
            return $next($request);
        }

        $admin = Auth::guard('super_admin')->user();

        $reason = match (true) {
            Impersonation::expired($data) => 'expired',
            $admin === null,
            $admin->id !== $data['admin_id'],
            ! ImpersonationService::canImpersonate($admin) => 'admin_signed_out',
            default => null,
        };

        if ($reason === null) {
            return $next($request);
        }

        app(ImpersonationService::class)->end($admin, $data['log_id'], $reason);
        Impersonation::forget();

        return redirect()->to($admin ? route('admin.users') : route('admin.login'));
    }
}
