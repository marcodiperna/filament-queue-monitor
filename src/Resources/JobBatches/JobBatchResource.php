<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Resources\JobBatches;

use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use MarcoDiPerna\FilamentQueueMonitor\Models\JobBatch;
use MarcoDiPerna\FilamentQueueMonitor\Resources\Concerns\BelongsToQueueMonitor;
use MarcoDiPerna\FilamentQueueMonitor\Resources\JobBatches\Pages\ListJobBatches;
use MarcoDiPerna\FilamentQueueMonitor\Support\Columns;
use MarcoDiPerna\FilamentQueueMonitor\Support\Operator;

/**
 * I lavori lanciati a gruppi: quanto manca e quanti sono falliti.
 */
class JobBatchResource extends Resource
{
    use BelongsToQueueMonitor;

    protected static ?string $model = JobBatch::class;

    protected static ?string $slug = 'queue-monitor/batches';

    protected static function monitorKey(): string
    {
        return 'batches';
    }

    protected static function monitorOffset(): int
    {
        return 2;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('10s')
            ->columns([
                TextColumn::make('name')->label(static::label('columns.name'))->weight('bold')->placeholder('—'),
                TextColumn::make('state')
                    ->label(static::label('columns.state'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => static::label('states.'.$state))
                    ->color(fn (string $state): string => match ($state) {
                        JobBatch::STATE_DONE => 'success',
                        JobBatch::STATE_DONE_WITH_FAILURES, JobBatch::STATE_CANCELLED => 'danger',
                        default => 'info',
                    }),
                TextColumn::make('progress')->label(static::label('columns.progress'))->suffix('%'),
                TextColumn::make('total_jobs')->label(static::label('columns.total'))->numeric(),
                TextColumn::make('failed_jobs')
                    ->label(static::label('columns.failures'))
                    ->numeric()
                    ->color(fn (int $state): ?string => $state > 0 ? 'danger' : null),
                TextColumn::make('started_at')->label(static::label('columns.started_at'))->dateTime('d/m/Y H:i:s'),
                TextColumn::make('ended_at')->label(static::label('columns.ended_at'))->dateTime('d/m/Y H:i:s')->placeholder('—'),
                Columns::operator()
                    ->visible(fn (): bool => Operator::enabled() && config('queue-monitor.history.enabled')),
            ])
            ->emptyStateHeading(static::label('batches.empty'))
            ->emptyStateIcon(Heroicon::OutlinedRectangleStack);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobBatches::route('/'),
        ];
    }
}
