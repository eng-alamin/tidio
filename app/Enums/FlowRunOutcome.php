<?php

namespace App\Enums;

enum FlowRunOutcome: string
{
    case Lead = 'lead';
    case Sale = 'sale';
    case Booking = 'booking';
    case None = 'none';
}
