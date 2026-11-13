<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Tests\Fixtures;

use Filament\Tables\Table;
use MarcoDiPerna\FilamentQueueMonitor\Resources\FailedJobs\FailedJobResource;

class CustomFailedJobResource extends FailedJobResource
{
    public static function table(Table $table): Table
    {
        return parent::table($table)->heading('Tabella dell\'app');
    }
}
