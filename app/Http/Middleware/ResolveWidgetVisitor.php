<?php

namespace App\Http\Middleware;

use App\Models\Visitor;
use App\Models\Website;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates a visitor by the server-issued session id (X-Widget-Session header).
 * Runs after ResolveWidgetWebsite and only ever looks inside that website's workspace,
 * so a session id can't be replayed against another tenant.
 */
class ResolveWidgetVisitor
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Website $website */
        $website = $request->attributes->get('widget_website');
        $sessionId = $request->header('X-Widget-Session');

        abort_unless(is_string($sessionId) && Str::isUuid($sessionId), 401, 'Session expired.');

        $visitor = Visitor::query()
            ->where('workspace_id', $website->workspace_id)
            ->where('session_id', $sessionId)
            ->first();

        abort_unless($visitor, 401, 'Session expired.');

        $request->attributes->set('widget_visitor', $visitor);

        return $next($request);
    }
}
