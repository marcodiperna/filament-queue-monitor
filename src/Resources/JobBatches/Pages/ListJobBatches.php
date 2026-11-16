<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Resources\JobBatches\Pages;

use Filament\Resources\Pages\ListRecords;
use MarcoDiPerna\FilamentQueueMonitor\QueueMonitorPlugin;
use MarcoDiPerna\FilamentQueueMonitor\Resources\JobBatches\JobBatchResource;

class ListJobBatches extends ListRecords
{
    protected static string $resource = JobBatchResource::class;

    /**
     * The resource configured in the plugin: if the app registered its own
     * subclass, table and actions are the subclass's.
     */
    public static function getResource(): string
    {
        return QueueMonitorPlugin::get()->getResource('batches');
    }

    /**
     * The title is the menu item's: whoever clicks "History" should land on
     * "History", not on the model name.
     */
    public function getTitle(): string
    {
        return static::getResource()::getNavigationLabel();
    }
}
