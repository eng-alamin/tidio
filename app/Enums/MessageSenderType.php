<?php

namespace App\Enums;

enum MessageSenderType: string
{
    case Visitor = 'visitor';
    case Operator = 'operator';
    case Bot = 'bot';
    case System = 'system';
}
