<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Resources\QueuedJobs;

use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use MarcoDiPerna\FilamentQueueMonitor\Models\QueuedJob;
use MarcoDiPerna\FilamentQueueMonitor\Resources\Concerns\BelongsToQueueMonitor;
use MarcoDiPerna\FilamentQueueMonitor\Resources\QueuedJobs\Pages\ListQueuedJobs;

/**
 * La coda: cosa aspetta il worker, cosa sta eseguendo e cosa partira' piu'
 * avanti. Sola lettura, tranne l'eliminazione di un job bloccato.
 */
class QueuedJobResource extends Resource
{
    use BelongsToQueueMonitor;

    protected static ?string $model = QueuedJob::class;

    protected static ?string $slug = 'queue-monitor/queue';

    protected static function monitorKey(): string
    {
        return 'queued';
    }

    protected static function monitorOffset(): int
    {
        return 0;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = QueuedJob::query()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->poll('10s')
            ->columns([
                TextColumn::make('name')->label(static::label('columns.job'))->weight('bold'),
                TextColumn::make('queue')->label(static::label('columns.queue'))->badge()->color('gray'),
                TextColumn::make('state')
                    ->label(static::label('columns.state'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => static::label('states.'.$state))
                    ->color(fn (string $state): string => match ($state) {
                        QueuedJob::STATE_RUNNING => 'info',
                        QueuedJob::STATE_SCHEDULED => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('attempts')->label(static::label('columns.attempts'))->numeric(),
                TextColumn::make('queued_at')->label(static::label('columns.queued_at'))->dateTime('d/m/Y H:i:s'),
                TextColumn::make('available_from')->label(static::label('columns.available_from'))->dateTime('d/m/Y H:i:s'),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->modalDescription(static::label('queued.delete_description')),
            ])
            ->emptyStateHeading(static::label('queued.empty'))
            ->emptyStateDescription(static::label('queued.empty_description'))
            ->emptyStateIcon(Heroicon::OutlinedQueueList);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQueuedJobs::route('/'),
        ];
    }
}
