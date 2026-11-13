<?php

declare(strict_types=1);

namespace P2TInformatica\FilamentQueueMonitor;

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
