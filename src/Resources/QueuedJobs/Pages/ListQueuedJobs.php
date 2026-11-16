<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Resources\QueuedJobs\Pages;

use Filament\Resources\Pages\ListRecords;
use MarcoDiPerna\FilamentQueueMonitor\QueueMonitorPlugin;
use MarcoDiPerna\FilamentQueueMonitor\Resources\QueuedJobs\QueuedJobResource;
use MarcoDiPerna\FilamentQueueMonitor\Widgets\QueueOverview;

class ListQueuedJobs extends ListRecords
{
    protected static string $resource = QueuedJobResource::class;

    /**
     * The resource configured in the plugin: if the app registered its own
     * subclass, table and actions are the subclass's.
     */
    public static function getResource(): string
    {
        return QueueMonitorPlugin::get()->getResource('queued');
    }

    /**
     * The title is the menu item's: whoever clicks "History" should land on
     * "History", not on the model name.
     */
    public function getTitle(): string
    {
        return static::getResource()::getNavigationLabel();
    }

    protected function getHeaderWidgets(): array
    {
        return [QueueOverview::class];
    }
}
