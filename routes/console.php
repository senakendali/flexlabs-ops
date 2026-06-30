<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('trello:sync-cards --source=academic')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('trello:sync-cards --source=marketing')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('trello:sync-cards --source=sei')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('meta-ads:sync-campaign-insights --date-preset=last_7d')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/meta-ads-sync.log'));