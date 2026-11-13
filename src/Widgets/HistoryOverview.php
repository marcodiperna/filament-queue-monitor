<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use MarcoDiPerna\FilamentQueueMonitor\Models\JobRun;
use MarcoDiPerna\FilamentQueueMonitor\Support\Duration;

/**
 * Le ultime 24 ore: quanto ha lavorato la coda, quanto e' andato storto e
 * quanto durano i job. Le linee sotto ai numeri sono l'andamento dei giorni
 * configurati in queue-monitor.history.stats_days.
 */
class HistoryOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $since = now()->subDay();
        $finished = JobRun::query()->where('started_at', '>=', $since)->whereNotNull('finished_at');

        $total = (clone $finished)->count();
        $failed = (clone $finished)->where('status', JobRun::STATUS_FAILED)->count();
        $average = (clone $finished)->avg('duration_ms');
        $sum = (int) (clone $finished)->sum('duration_ms');

        $days = collect(range(JobRun::statsDays() - 1, 0))->map(fn (int $ago): Carbon => now()->subDays($ago)->startOfDay());
        $perDay = fn (?string $status = null) => $days->map(fn (Carbon $day): int => JobRun::query()
            ->whereBetween('started_at', [$day, $day->copy()->endOfDay()])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->count())->all();

        $label = fn (string $key, array $replace = []): string => __('filament-queue-monitor::queue-monitor.stats.'.$key, $replace);

        return [
            Stat::make($label('runs'), number_format($total, 0, ',', '.'))
                ->description($label('last_24h'))
                ->chart($perDay())
                ->color('primary'),
            Stat::make($label('failed'), number_format($failed, 0, ',', '.'))
                ->description($total > 0 ? $label('failure_rate', ['rate' => number_format($failed / $total * 100, 1, ',', '.')]) : $label('last_24h'))
                ->chart($perDay(JobRun::STATUS_FAILED))
                ->color($failed > 0 ? 'danger' : 'success'),
            Stat::make($label('average_duration'), Duration::format($average))
                ->description($label('last_24h')),
            Stat::make($label('total_time'), Duration::format($sum))
                ->description($label('last_24h')),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
