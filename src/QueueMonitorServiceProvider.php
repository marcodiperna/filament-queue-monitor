<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use MarcoDiPerna\FilamentQueueMonitor\Models\JobRun;
use MarcoDiPerna\FilamentQueueMonitor\Support\Operator;
use MarcoDiPerna\FilamentQueueMonitor\Support\RunRecorder;

/**
 * Lo storico e l'operatore si attivano qui, dalla configurazione, e non dal
 * plugin: devono girare anche nel worker, dove i pannelli Filament non si
 * caricano.
 */
class QueueMonitorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/queue-monitor.php', 'queue-monitor');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'filament-queue-monitor');

        $this->publishes([
            __DIR__.'/../config/queue-monitor.php' => config_path('queue-monitor.php'),
        ], 'filament-queue-monitor-config');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/filament-queue-monitor'),
        ], 'filament-queue-monitor-translations');

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'filament-queue-monitor-migrations');

        if (Operator::enabled()) {
            Operator::register();
        }

        if (config('queue-monitor.history.enabled')) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

            RunRecorder::register();

            $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
                $schedule->command('model:prune', ['--model' => [JobRun::class]])->daily();
            });
        }
    }
}
