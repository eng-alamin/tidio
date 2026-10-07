<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
| Operator channel: every active member of a workspace hears "a conversation changed" for THAT
| workspace only. Visitors never use this file — their channel is authorised by
| WidgetBroadcastAuthController through their widget session.
*/
Broadcast::channel('workspace.{workspaceId}', function (User $user, int $workspaceId): bool {
    return $user->workspaces()
        ->where('workspaces.id', $workspaceId)
        ->wherePivot('status', 'active')
        ->exists();
});
