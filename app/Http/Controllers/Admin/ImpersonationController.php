<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SuperAdmin\ImpersonationService;
use App\Support\Impersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    /**
     * Ends the impersonation in this browser session and returns to the admin panel.
     * Does nothing to a normal tenant login, so it can never sign a real user out.
     */
    public function stop(ImpersonationService $service): RedirectResponse
    {
        $admin = Auth::guard('super_admin')->user();
        $data = Impersonation::data();

        if ($data !== null) {
            $service->end($admin, $data['log_id'], 'stopped');
            Impersonation::forget();
        }

        return redirect()->route($admin ? 'admin.users' : 'admin.login');
    }
}
