<?php

namespace App\Enums;

enum AiDataSourceStatus: string
{
    case Pending = 'pending';
    case Syncing = 'syncing';
    case Synced = 'synced';
    case Failed = 'failed';
}
