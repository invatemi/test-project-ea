<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        $times = array_map('intval', explode(',', env('IMPORT_SCHEDULE_TIMES', '8,20')));

        foreach ($times as $hour) {
            $schedule->command('app:import-all --all-accounts')
                ->dailyAt(sprintf('%02d:00', $hour))
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/scheduler.log'));
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
