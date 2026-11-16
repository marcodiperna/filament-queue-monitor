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
use MarcoDiPerna\FilamentQueueMonitor\QueueMonitorPlugin;
use MarcoDiPerna\FilamentQueueMonitor\Resources\Concerns\BelongsToQueueMonitor;
use MarcoDiPerna\FilamentQueueMonitor\Resources\FailedJobs\Pages\ListFailedJobs;
use MarcoDiPerna\FilamentQueueMonitor\Support\Columns;
use MarcoDiPerna\FilamentQueueMonitor\Support\ErrorModal;

/**
 * Failed jobs: the error, to understand what went wrong, and the option to
 * retry them once the cause is fixed.
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
     * Pushes the given jobs back onto the queue: it is the same queue:retry as
     * the command line, so the behaviour never drifts from Laravel's.
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
            TextEntry::make('name')
                ->label(static::label('columns.job'))
                ->formatStateUsing(fn (?string $state): string => QueueMonitorPlugin::get()->formatJobName($state))
                ->helperText(fn (?string $state): ?string => $state),
            TextEntry::make('queue')->label(static::label('columns.queue')),
            TextEntry::make('failed_at')->label(static::label('columns.failed_at'))->dateTime('d/m/Y H:i:s'),
            TextEntry::make('uuid')->label(static::label('columns.uuid'))->copyable(),
            ErrorModal::entry(label: static::label('columns.error')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('failed_at', 'desc')
            ->columns([
                Columns::jobName(),
                Columns::queue(),
                Columns::error(),
                TextColumn::make('failed_at')->label(static::label('columns.failed_at'))->dateTime('d/m/Y H:i:s')->sortable(),
                Columns::operator(),
            ])
            ->recordActions(QueueMonitorPlugin::get()->recordActions([
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
            ]))
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
