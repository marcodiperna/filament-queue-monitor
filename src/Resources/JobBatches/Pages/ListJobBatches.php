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
     * La resource configurata nel plugin: se l'app ne ha registrata una sua
     * sottoclasse, tabella e azioni sono le sue.
     */
    public static function getResource(): string
    {
        return QueueMonitorPlugin::get()->getResource('batches');
    }

    /**
     * Il titolo e' quello della voce di menu: chi clicca "Storico" deve
     * trovare "Storico", non il nome del modello.
     */
    public function getTitle(): string
    {
        return static::getResource()::getNavigationLabel();
    }
}
