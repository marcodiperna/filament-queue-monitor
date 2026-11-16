<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use MarcoDiPerna\FilamentQueueMonitor\Models\QueuedJob;

/**
 * The queue in three numbers, and how long the oldest job has been waiting:
 * if it grows, the worker is not running or cannot keep up.
 */
class QueueOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        $now = now()->timestamp;
        $query = fn () => QueuedJob::query();

        $oldest = $query()->whereNull('reserved_at')->where('available_at', '<=', $now)->min('available_at');

        return [
            Stat::make(__('filament-queue-monitor::queue-monitor.states.waiting'), $query()->whereNull('reserved_at')->where('available_at', '<=', $now)->count())
                ->description($oldest
                    ? __('filament-queue-monitor::queue-monitor.stats.oldest_waiting', ['time' => QueuedJob::time($oldest)->diffForHumans(syntax: true)])
                    : null)
                ->color($oldest && $now - $oldest > 300 ? 'warning' : 'gray'),
            Stat::make(__('filament-queue-monitor::queue-monitor.states.running'), $query()->whereNotNull('reserved_at')->count()),
            Stat::make(__('filament-queue-monitor::queue-monitor.states.scheduled'), $query()->whereNull('reserved_at')->where('available_at', '>', $now)->count()),
        ];
    }
}
