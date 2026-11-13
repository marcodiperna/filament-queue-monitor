<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Resources\JobBatches\Pages;

use Filament\Resources\Pages\ListRecords;
use MarcoDiPerna\FilamentQueueMonitor\Resources\JobBatches\JobBatchResource;

class ListJobBatches extends ListRecords
{
    protected static string $resource = JobBatchResource::class;
}
