<?php

namespace App\Http\Controllers\Widget;

use App\Http\Controllers\Controller;
use App\Models\Visitor;
use App\Services\Realtime\RealtimeConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pusher-protocol channel authorisation for visitors (they are not Laravel users, so the stock
 * /broadcasting/auth can't be used). A visitor may only join THEIR OWN private channel; the
 * X-Widget-Session header proves who they are (checked by the widget.visitor middleware).
 */
class WidgetBroadcastAuthController extends Controller
{
    public function __construct(private readonly RealtimeConfig $realtime)
    {
    }

    public function __invoke(Request $request, string $widgetKey): JsonResponse
    {
        abort_unless($this->realtime->enabled(), 404);

        /** @var Visitor $visitor */
        $visitor = $request->attributes->get('widget_visitor');

        $socketId = (string) $request->input('socket_id');
        $channel = (string) $request->input('channel_name');

        abort_unless(preg_match('/^\d{1,20}\.\d{1,20}$/', $socketId) === 1, 422, 'Invalid socket id.');
        abort_unless($channel === 'private-'.$this->realtime->visitorChannel($visitor->id), 403);

        return response()
            ->json(['auth' => $this->realtime->signature($socketId, $channel)])
            ->header('Cache-Control', 'no-store');
    }
}
