<?php

namespace App\Http\Middleware;

use App\Services\SuperAdmin\PlatformSettingsService;
use App\Support\Impersonation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locks the tenant app panel while "Maintenance mode" is on in the Super Admin settings.
 * Platform staff impersonating a tenant are let through. The public chat widget is not affected.
 */
class BlockDuringMaintenance
{
    public function __construct(private readonly PlatformSettingsService $settings)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->settings->maintenanceMode() && ! Impersonation::active()) {
            abort(503, $this->settings->maintenanceMessage());
        }

        return $next($request);
    }
}
