<?php

namespace App\Http\Controllers\Widget;

use App\Http\Controllers\Controller;
use App\Http\Requests\Widget\IdentifyRequest;
use App\Http\Requests\Widget\InitRequest;
use App\Models\Visitor;
use App\Models\Website;
use App\Services\Widget\WidgetChatService;
use App\Services\Widget\WidgetConfigBuilder;
use Illuminate\Http\JsonResponse;

class WidgetSessionController extends Controller
{
    public function __construct(
        private readonly WidgetChatService $chat,
        private readonly WidgetConfigBuilder $config,
    ) {
    }

    /** Starts (or resumes) a visitor session and returns the current conversation, if any. */
    public function init(InitRequest $request, string $widgetKey): JsonResponse
    {
        /** @var Website $website */
        $website = $request->attributes->get('widget_website');

        $visitor = $this->chat->startOrResumeSession(
            $website,
            $request->input('session_id'),
            $request->ip(),
            $request->userAgent(),
            $request->input('page_url'),
        );

        $website->markInstalledFrom($request->input('page_url'));

        return $this->json([
            'session_id' => $visitor->session_id,
            'online' => $this->config->isOnline($website),
        ] + $this->chat->snapshot($visitor));
    }

    /** Optional name / email capture. */
    public function identify(IdentifyRequest $request, string $widgetKey): JsonResponse
    {
        /** @var Visitor $visitor */
        $visitor = $request->attributes->get('widget_visitor');

        $this->chat->identify($visitor, $request->input('name') ?: null, $request->input('email') ?: null);

        return $this->json(['identified' => true]);
    }

    private function json(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status)->header('Cache-Control', 'no-store');
    }
}
