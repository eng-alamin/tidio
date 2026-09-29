<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBelongsToWorkspace
{
    // Every API route below is nested under /workspaces/{workspace}/..., so
    // this runs once per request and rejects cross-tenant access before any
    // controller code runs — the core of the multi-tenancy boundary at the
    // HTTP layer (the DB layer's boundary is workspace_id itself).
    public function handle(Request $request, Closure $next): Response
    {
        // $workspace = $request->route('workspace');

        // if (! $workspace instanceof Workspace) {
        //     abort(404);
        // }

        // $belongs = $workspace->users()
        //     ->where('users.id', $request->user()->id)
        //     ->wherePivot('status', 'active')
        //     ->exists();

        // abort_unless($belongs, 403, 'You are not a member of this workspace.');

        // return $next($request);

        $workspace = $request->route('workspace');
 
        if ($workspace instanceof Workspace) {
            $this->authorize($request, $workspace);
            return $next($request);
        }
 
        // No {workspace} route param — this is a web app-panel request.
        // Resolve the user's current workspace instead of requiring one in the URL.
        $user = $request->user();
        abort_unless($user, 401);
 
        $workspaceId = $request->session()->get('current_workspace_id');
        $activeWorkspaces = $user->workspaces()->wherePivot('status', 'active');
 
        $membership = $workspaceId
            ? (clone $activeWorkspaces)->where('workspaces.id', $workspaceId)->first()
            : null;
 
        $membership ??= $activeWorkspaces->first();
 
        abort_unless($membership, 403, 'You are not a member of any workspace yet.');
 
        $request->session()->put('current_workspace_id', $membership->id);
 
        // Make it available the same way a route-bound {workspace} would be:
        // $request->route('workspace') for consistency, plus a container alias.
        $request->attributes->set('workspace', $membership);
        app()->instance('currentWorkspace', $membership);
 
        return $next($request);
    }

    private function authorize(Request $request, Workspace $workspace): void
    {
        $belongs = $workspace->users()
            ->where('users.id', $request->user()->id)
            ->wherePivot('status', 'active')
            ->exists();
 
        abort_unless($belongs, 403, 'You are not a member of this workspace.');
    }
}
