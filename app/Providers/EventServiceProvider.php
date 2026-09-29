<?php

namespace App\Providers;

use App\Events\ConversationCreated;
use App\Events\ConversationResolved;
use App\Events\MessageSent;
use App\Listeners\ApplyWorkflowRules;
use App\Listeners\SendCsatSurvey;
use App\Listeners\UpdateConversationMetricsOnMessage;
use App\Listeners\UpdateResolutionMetric;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

// Laravel 11+ note: if using the new slim bootstrap/app.php structure,
// register these same pairs via Event::listen(...) calls in
// bootstrap/app.php or AppServiceProvider::boot() instead — this classic
// $listen array form still works on 10.x and works on 11.x too if you keep
// this provider registered in bootstrap/providers.php.
class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ConversationCreated::class => [
            ApplyWorkflowRules::class,
        ],
        MessageSent::class => [
            UpdateConversationMetricsOnMessage::class,
        ],
        ConversationResolved::class => [
            UpdateResolutionMetric::class,
            SendCsatSurvey::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
