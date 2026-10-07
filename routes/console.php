<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Visitors who closed the tab stop sending heartbeats; this flips them to "offline".
Schedule::command('widget:mark-offline')->everyMinute()->withoutOverlapping();

// One MRR data point per day for the Super Admin revenue trend chart.
Schedule::command('mrr:snapshot')->dailyAt('00:05')->withoutOverlapping();
