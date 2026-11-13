<?php

declare(strict_types=1);

namespace P2TInformatica\FilamentQueueMonitor\Resources\FailedJobs\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use P2TInformatica\FilamentQueueMonitor\Models\FailedJob;
use P2TInformatica\FilamentQueueMonitor\Resources\FailedJobs\FailedJobResource;

class ListFailedJobs extends ListRecords
{
    protected static string $resource = FailedJobResource::class;

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
