<?php

namespace App\Http\Controllers\Widget;

use App\Enums\ConversationChannelType;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Services\AttachmentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves a file to the visitor who owns the conversation. The route is `signed` (short-lived URL
 * created while presenting the message), because <img src> cannot send the session header.
 * Internal notes and other channels' messages are never served from here.
 */
class WidgetAttachmentController extends Controller
{
    public function __construct(private readonly AttachmentService $attachments)
    {
    }

    public function __invoke(Request $request, int $message, string $attachment): Response
    {
        $row = Message::query()
            ->with('conversation:id,workspace_id,channel_type,visitor_id')
            ->whereKey($message)
            ->where('is_private_note', false)
            ->first();

        abort_unless(
            $row
            && $row->conversation
            && $row->conversation->visitor_id !== null
            && $row->conversation->channel_type === ConversationChannelType::Widget,
            404
        );

        $descriptor = $this->attachments->find($row->attachments, $attachment);

        abort_unless($descriptor, 404);

        return $this->attachments->response($descriptor);
    }
}
