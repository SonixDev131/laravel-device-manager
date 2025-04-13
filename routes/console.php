<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Define the application's command schedule.
 */
Schedule::command('computers:check-timeouts')
    ->everyMinute()
    ->appendOutputTo(storage_path('logs/scheduled-commands.log'))
    ->withoutOverlapping();
