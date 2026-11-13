<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor;

use Illuminate\Support\ServiceProvider;

class QueueMonitorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'filament-queue-monitor');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/filament-queue-monitor'),
        ], 'filament-queue-monitor-translations');
    }
}
