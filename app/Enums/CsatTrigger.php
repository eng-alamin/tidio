<?php

namespace App\Enums;

enum CsatTrigger: string
{
    case AfterResolution = 'after_resolution';
    case Manual = 'manual';
}
