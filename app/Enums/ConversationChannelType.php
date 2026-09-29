<?php

namespace App\Enums;

// Where a conversation originated. Broader than App\Enums\ChannelType,
// because a conversation can start on the embedded widget itself
// (no `channels` row involved), unlike a connected integration.
enum ConversationChannelType: string
{
    case Widget = 'widget';
    case Whatsapp = 'whatsapp';
    case Messenger = 'messenger';
    case Instagram = 'instagram';
    case Email = 'email';
}
