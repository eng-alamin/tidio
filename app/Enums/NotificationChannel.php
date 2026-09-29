<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case Email = 'email';
    case Push = 'push';
    case Desktop = 'desktop';
}
