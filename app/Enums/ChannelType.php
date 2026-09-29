<?php

namespace App\Enums;

// Connected third-party integrations only (see App\Enums\ConversationChannelType
// for the widget-inclusive version used on the conversations table).
enum ChannelType: string
{
    case Whatsapp = 'whatsapp';
    case Messenger = 'messenger';
    case Instagram = 'instagram';
    case Email = 'email';
}
