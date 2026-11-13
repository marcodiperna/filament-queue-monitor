<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Resources\FailedJobs;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use MarcoDiPerna\FilamentQueueMonitor\Models\FailedJob;
use MarcoDiPerna\FilamentQueueMonitor\Resources\Concerns\BelongsToQueueMonitor;
use MarcoDiPerna\FilamentQueueMonitor\Resources\FailedJobs\Pages\ListFailedJobs;

/**
 * I job falliti: l'errore per capire cosa e' andato storto e la possibilita'
 * di riprovarli una volta sistemata la causa.
 */
class FailedJobResource extends Resource
{
    use BelongsToQueueMonitor;

    protected static ?string $model = FailedJob::class;

    protected static ?string $slug = 'queue-monitor/failed';

    protected static ?string $recordTitleAttribute = 'name';

    protected static function monitorKey(): string
    {
        return 'failed';
    }

    protected static function monitorOffset(): int
    {
        return 1;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = FailedJob::query()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /**
     * Rimette in coda i job dati: e' lo stesso queue:retry della riga di
     * comando, cosi' il comportamento non si discosta da quello di Laravel.
     *
     * @param  array<int, string>  $uuids
     */
    public static function retry(array $uuids): void
    {
        Artisan::call('queue:retry', ['id' => $uuids]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextEntry::make('name')->label(static::label('columns.job')),
            TextEntry::make('queue')->label(static::label('columns.queue')),
            TextEntry::make('failed_at')->label(static::label('columns.failed_at'))->dateTime('d/m/Y H:i:s'),
            TextEntry::make('uuid')->label(static::label('columns.uuid'))->copyable(),
            TextEntry::make('exception')
                ->label(static::label('columns.error'))
                ->columnSpanFull()
                ->extraAttributes(['style' => 'font-family: ui-monospace, monospace; font-size: 0.75rem; white-space: pre-wrap; max-height: 24rem; overflow-y: auto']),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('failed_at', 'desc')
            ->columns([
                TextColumn::make('name')->label(static::label('columns.job'))->weight('bold'),
                TextColumn::make('queue')->label(static::label('columns.queue'))->badge()->color('gray'),
                TextColumn::make('error')->label(static::label('columns.error'))->wrap()->color('danger'),
                TextColumn::make('failed_at')->label(static::label('columns.failed_at'))->dateTime('d/m/Y H:i:s')->sortable(),
            ])
            ->recordActions([
                ViewAction::make()->modalWidth(Width::FiveExtraLarge),
                Action::make('retry')
                    ->label(static::label('failed.retry'))
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('primary')
                    ->action(function (FailedJob $record): void {
                        static::retry([$record->uuid]);
                        Notification::make()->success()->title(trans_choice('filament-queue-monitor::queue-monitor.failed.retried', 1))->send();
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('retrySelected')
                        ->label(static::label('failed.retry_selected'))
                        ->icon(Heroicon::OutlinedArrowPath)
                        ->action(function (Collection $records): void {
                            static::retry($records->pluck('uuid')->all());
                            Notification::make()
                                ->success()
                                ->title(trans_choice('filament-queue-monitor::queue-monitor.failed.retried', $records->count(), ['count' => $records->count()]))
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(static::label('failed.empty'))
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFailedJobs::route('/'),
        ];
    }
}
