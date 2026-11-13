<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Resources\QueuedJobs\Pages;

use Filament\Resources\Pages\ListRecords;
use MarcoDiPerna\FilamentQueueMonitor\Resources\QueuedJobs\QueuedJobResource;

class ListQueuedJobs extends ListRecords
{
    protected static string $resource = QueuedJobResource::class;
}
