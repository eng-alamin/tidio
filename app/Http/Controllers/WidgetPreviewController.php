<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Blank page that embeds the signed-in workspace's own widget, so you can try the full
 * visitor ⇄ Inbox round trip from Settings > Installation without touching another website.
 * Lives inside the authenticated app group.
 */
class WidgetPreviewController extends Controller
{
    public function __invoke(Request $request): View
    {
        $workspace = app('currentWorkspace');

        $website = $workspace->websites()->first();

        abort_unless($website, 404);

        return view('widget.preview', [
            'src' => url('/widget/'.$website->widget_key.'.js'),
            'workspaceName' => $workspace->name,
        ]);
    }
}
