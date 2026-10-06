<?php

namespace App\Http\Middleware;

use App\Models\Website;
use App\Services\Widget\WidgetOriginPolicy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * First gate of every public widget request (loader, chat frame, JSON API):
 * resolves the website from the URL's widget key, refuses unknown / suspended workspaces,
 * and rejects browser calls coming from a page that isn't allowed to embed the widget.
 */
class ResolveWidgetWebsite
{
    public function __construct(private readonly WidgetOriginPolicy $origins)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) $request->route('widgetKey');

        abort_unless(Str::isUuid($key), 404);

        $website = Website::query()
            ->with(['workspace', 'widgetSetting'])
            ->where('widget_key', $key)
            ->first();

        // workspace is null when the workspace was (soft) deleted.
        abort_if(! $website || ! $website->workspace, 404);
        abort_if($website->workspace->is_suspended, 403, 'Chat is unavailable.');

        abort_unless(
            $this->origins->allowsOrigin($website, $request->headers->get('Origin'), $request->getHost()),
            403,
            'This site is not allowed to use this chat widget.'
        );

        $request->attributes->set('widget_website', $website);

        return $next($request);
    }
}
