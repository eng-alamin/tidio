<?php

namespace App\Enums;

enum FlowTriggerType: string
{
    case PageVisit = 'page_visit';
    case TimeOnPage = 'time_on_page';
    case ExitIntent = 'exit_intent';
    case CartAbandonment = 'cart_abandonment';
    case Manual = 'manual';
}
