<?php

namespace App\Console\Commands;

use App\Models\Visitor;
use Illuminate\Console\Command;

class MarkWidgetVisitorsOffline extends Command
{
    protected $signature = 'widget:mark-offline';

    protected $description = 'Flag widget visitors with no recent heartbeat as offline';

    public function handle(): int
    {
        $count = Visitor::query()
            ->where('is_online', true)
            ->where('last_seen_at', '<', now()->subSeconds((int) config('widget.online_ttl_seconds', 120)))
            ->update(['is_online' => false]);

        $this->info("Marked {$count} visitor(s) offline.");

        return self::SUCCESS;
    }
}
