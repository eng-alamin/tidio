<?php

namespace App\Enums;

enum FlowStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Paused = 'paused';
}
