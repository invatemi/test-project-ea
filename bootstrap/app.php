<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Jobs\ImportAllAccountsJob;
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
        foreach (config('import.schedule_times') as $hour) {
            $schedule->job(new ImportAllAccountsJob)
                ->dailyAt(sprintf('%02d:00', $hour))
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/scheduler.log'));
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
