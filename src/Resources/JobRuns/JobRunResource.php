<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Resources\JobRuns;

use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use MarcoDiPerna\FilamentQueueMonitor\Models\JobRun;
use MarcoDiPerna\FilamentQueueMonitor\QueueMonitorPlugin;
use MarcoDiPerna\FilamentQueueMonitor\Resources\Concerns\BelongsToQueueMonitor;
use MarcoDiPerna\FilamentQueueMonitor\Resources\JobRuns\Pages\ListJobRuns;
use MarcoDiPerna\FilamentQueueMonitor\Support\Columns;
use MarcoDiPerna\FilamentQueueMonitor\Support\Duration;
use MarcoDiPerna\FilamentQueueMonitor\Widgets\HistoryOverview;

/**
 * The run history: what ran, how long it took, how it ended and who started
 * it.
 */
class JobRunResource extends Resource
{
    use BelongsToQueueMonitor;

    protected static ?string $model = JobRun::class;

    protected static ?string $slug = 'queue-monitor/history';

    protected static function monitorKey(): string
    {
        return 'history';
    }

    protected static function monitorOffset(): int
    {
        return 3;
    }

    public static function getWidgets(): array
    {
        return [HistoryOverview::class];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('started_at', 'desc')
            ->poll('10s')
            ->columns([
                Columns::jobName(),
                Columns::queue(),
                TextColumn::make('status')
                    ->label(static::label('columns.state'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => static::label('states.'.$state))
                    ->color(fn (string $state): string => match ($state) {
                        JobRun::STATUS_SUCCEEDED => 'success',
                        JobRun::STATUS_FAILED => 'danger',
                        JobRun::STATUS_RETRIED => 'warning',
                        default => 'info',
                    }),
                TextColumn::make('attempt')->label(static::label('columns.attempts'))->numeric(),
                TextColumn::make('started_at')->label(static::label('columns.started_at'))->dateTime('d/m/Y H:i:s')->sortable(),
                TextColumn::make('duration_ms')
                    ->label(static::label('columns.duration'))
                    ->formatStateUsing(fn (?int $state): string => Duration::format($state))
                    ->placeholder('—')
                    ->sortable(),
                Columns::operator(),
                Columns::error()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(static::label('columns.state'))
                    ->options(collect([JobRun::STATUS_RUNNING, JobRun::STATUS_SUCCEEDED, JobRun::STATUS_FAILED, JobRun::STATUS_RETRIED])
                        ->mapWithKeys(fn (string $status): array => [$status => static::label('states.'.$status)])
                        ->all()),
                SelectFilter::make('name')
                    ->label(static::label('columns.job'))
                    ->searchable()
                    ->options(fn (): array => JobRun::query()->distinct()->orderBy('name')->pluck('name', 'name')
                        ->map(fn (string $name): string => QueueMonitorPlugin::get()->formatJobName($name))
                        ->all()),
                Filter::make('today')
                    ->label(static::label('filters.today'))
                    ->query(fn (Builder $query): Builder => $query->where('started_at', '>=', now()->startOfDay())),
            ])
            ->emptyStateHeading(static::label('history.empty'))
            ->emptyStateIcon(Heroicon::OutlinedClock);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobRuns::route('/'),
        ];
    }
}
