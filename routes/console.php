<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule cache eviction to run every hour
Schedule::command('cache:evict-custom --force')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/cache-eviction.log'));

// Schedule cache eviction for file store every 30 minutes (more frequent for file cache)
Schedule::command('cache:evict-custom file --force')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/cache-eviction.log'));

// Schedule game tick processing every minute
Schedule::command('game:tick')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/game-tick.log'));

// Schedule training queue processing every 5 minutes
Schedule::command('game:process-training-queues')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/training-queues.log'));

// Schedule performance monitoring every 10 minutes
Schedule::command('game:performance-audit')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/performance-audit.log'));

// Schedule cleanup tasks daily at midnight
Schedule::command('game:cleanup')
    ->daily()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/cleanup.log'));
