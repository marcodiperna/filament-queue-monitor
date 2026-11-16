<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Resources\FailedJobs\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use MarcoDiPerna\FilamentQueueMonitor\Models\FailedJob;
use MarcoDiPerna\FilamentQueueMonitor\QueueMonitorPlugin;
use MarcoDiPerna\FilamentQueueMonitor\Resources\FailedJobs\FailedJobResource;

class ListFailedJobs extends ListRecords
{
    protected static string $resource = FailedJobResource::class;

    /**
     * The resource configured in the plugin: if the app registered its own
     * subclass, table and actions are the subclass's.
     */
    public static function getResource(): string
    {
        return QueueMonitorPlugin::get()->getResource('failed');
    }

    /**
     * The title is the menu item's: whoever clicks "History" should land on
     * "History", not on the model name.
     */
    public function getTitle(): string
    {
        return static::getResource()::getNavigationLabel();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('retryAll')
                ->label(__('filament-queue-monitor::queue-monitor.failed.retry_all'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->visible(fn (): bool => FailedJob::query()->exists())
                ->requiresConfirmation()
                ->modalDescription(__('filament-queue-monitor::queue-monitor.failed.retry_all_description'))
                ->action(function (): void {
                    $count = FailedJob::query()->count();
                    FailedJobResource::retry(['all']);
                    Notification::make()
                        ->success()
                        ->title(trans_choice('filament-queue-monitor::queue-monitor.failed.retried', $count, ['count' => $count]))
                        ->send();
                }),
            Action::make('flush')
                ->label(__('filament-queue-monitor::queue-monitor.failed.flush'))
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->visible(fn (): bool => FailedJob::query()->exists())
                ->requiresConfirmation()
                ->modalDescription(__('filament-queue-monitor::queue-monitor.failed.flush_description'))
                ->action(function (): void {
                    Artisan::call('queue:flush');
                    Notification::make()->success()->title(__('filament-queue-monitor::queue-monitor.failed.flushed'))->send();
                }),
        ];
    }
}
