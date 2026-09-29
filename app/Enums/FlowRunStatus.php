<?php

namespace App\Enums;

enum FlowRunStatus: string
{
    case Running = 'running';
    case Completed = 'completed';
    case Exited = 'exited';
}
