<?php

namespace App\Enums;

enum AiActionType: string
{
    case Mcp = 'mcp';
    case Webhook = 'webhook';
    case InternalLookup = 'internal_lookup';
}
