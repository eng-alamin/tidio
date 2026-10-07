<?php

namespace App\Console\Commands;

use App\Services\SuperAdmin\MrrSnapshotService;
use Illuminate\Console\Command;

class RecordMrrSnapshot extends Command
{
    protected $signature = 'mrr:snapshot';

    protected $description = "Record today's monthly recurring revenue snapshot for the Super Admin revenue trend";

    public function handle(MrrSnapshotService $service): int
    {
        $snapshot = $service->record();

        $this->info(sprintf(
            'MRR snapshot for %s: %s (%d active subscriptions).',
            $snapshot->snapshot_date->toDateString(),
            number_format($snapshot->mrr_cents / 100, 2),
            $snapshot->active_subscriptions
        ));

        return self::SUCCESS;
    }
}
