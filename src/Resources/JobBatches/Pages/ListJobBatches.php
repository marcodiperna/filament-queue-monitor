<?php

declare(strict_types=1);

namespace P2TInformatica\FilamentQueueMonitor\Resources\JobBatches\Pages;

use Filament\Resources\Pages\ListRecords;
use P2TInformatica\FilamentQueueMonitor\Resources\JobBatches\JobBatchResource;

class ListJobBatches extends ListRecords
{
    protected static string $resource = JobBatchResource::class;
}
