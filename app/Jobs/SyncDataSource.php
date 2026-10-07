<?php

namespace App\Jobs;

use App\Models\AiDataSource;
use App\Services\Knowledge\DataSourceSyncer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Reads a Lyro data source (website / PDF). Dispatched with dispatchAfterResponse() by default —
 * it runs right after the page that added the source has been sent, so no queue worker is needed
 * — or onto the queue when LYRO_CRAWL_DISPATCH=queue.
 */
class SyncDataSource implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public readonly int $sourceId)
    {
    }

    public static function start(int $sourceId): void
    {
        config('lyro.crawl.dispatch', 'after_response') === 'queue'
            ? static::dispatch($sourceId)
            : static::dispatchAfterResponse($sourceId);
    }

    public function handle(DataSourceSyncer $syncer): void
    {
        set_time_limit(300); // a 10-page crawl with pauses can take a while

        $source = AiDataSource::find($this->sourceId);

        if ($source) {
            $syncer->sync($source);
        }
    }
}
