<?php

namespace App\Http\Controllers\Widget;

use App\Http\Controllers\Controller;
use App\Http\Requests\Widget\PollRequest;
use App\Http\Requests\Widget\SendMessageRequest;
use App\Models\Visitor;
use App\Services\Widget\WidgetChatService;
use Illuminate\Http\JsonResponse;

class WidgetMessageController extends Controller
{
    public function __construct(private readonly WidgetChatService $chat)
    {
    }

    /** Polled by the chat frame: messages newer than ?after=<id>. */
    public function index(PollRequest $request, string $widgetKey): JsonResponse
    {
        /** @var Visitor $visitor */
        $visitor = $request->attributes->get('widget_visitor');

        $payload = $this->chat->poll($visitor, (int) $request->input('after', 0), $request->input('page_url'));

        return response()->json($payload)->header('Cache-Control', 'no-store');
    }

    /** JSON or multipart (when files are attached). Throws ConversationLimitReached (403) when full. */
    public function store(SendMessageRequest $request, string $widgetKey): JsonResponse
    {
        /** @var Visitor $visitor */
        $visitor = $request->attributes->get('widget_visitor');

        $payload = $this->chat->sendVisitorMessage(
            $visitor,
            (string) $request->validated('body'),
            $request->input('page_url'),
            array_values($request->file('files', [])),
        );

        return response()->json($payload, 201)->header('Cache-Control', 'no-store');
    }
}
