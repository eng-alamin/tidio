<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Services\AttachmentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Operator download. Behind `auth` + `workspace`: only the conversation's own workspace may read it. */
class AttachmentController extends Controller
{
    public function __construct(private readonly AttachmentService $attachments)
    {
    }

    public function __invoke(Request $request, int $message, string $attachment): Response
    {
        $workspaceId = app('currentWorkspace')->id;

        $row = Message::query()
            ->whereKey($message)
            ->whereHas('conversation', fn ($q) => $q->where('workspace_id', $workspaceId))
            ->first();

        abort_unless($row, 404);

        $descriptor = $this->attachments->find($row->attachments, $attachment);

        abort_unless($descriptor, 404);

        return $this->attachments->response($descriptor);
    }
}
