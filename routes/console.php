<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Trello Sync Schedules
|--------------------------------------------------------------------------
| Sync Trello cards untuk dashboard work progress.
*/
Schedule::command('trello:sync-cards --source=academic')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/trello-academic-sync.log'));

Schedule::command('trello:sync-cards --source=marketing')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/trello-marketing-sync.log'));

Schedule::command('trello:sync-cards --source=sei')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/trello-sei-sync.log'));

/*
|--------------------------------------------------------------------------
| Meta Ads Sync + AI Analysis
|--------------------------------------------------------------------------
| Sync campaign insight Meta Ads untuk dashboard.
|
| --date-preset=last_7d:
| Ambil data agregat 7 hari terakhir.
|
| --with-ai:
| Setelah sync data Meta Ads, generate summary, faktor penghambat,
| dan step-by-step solusi memakai Gemini Flash-Lite.
*/
Schedule::command('meta-ads:sync-campaign-insights --date-preset=last_7d --with-ai')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/meta-ads-sync.log'));


Schedule::command('google-analytics:sync-dashboard --date-preset=last_7d')
    ->everyThreeHours()
    ->withoutOverlapping();

Schedule::command('google-analytics:sync-dashboard --date-preset=last_7d --with-ai')
    ->dailyAt('07:10')
    ->withoutOverlapping();

Schedule::command('google-analytics:sync-dashboard --date-preset=last_30d --with-ai')
    ->dailyAt('07:20')
    ->withoutOverlapping();

Schedule::command('google-ads:sync-dashboard --date-preset=last_7d')
    ->everyThreeHours()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/google-ads-sync.log'));

Schedule::command('google-ads:sync-dashboard --date-preset=last_7d --with-ai')
    ->dailyAt('07:30')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/google-ads-ai-last-7d.log'));

Schedule::command('google-ads:sync-dashboard --date-preset=last_30d --with-ai')
    ->dailyAt('07:40')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/google-ads-ai-last-30d.log'));