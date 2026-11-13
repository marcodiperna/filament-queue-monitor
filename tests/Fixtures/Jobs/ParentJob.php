<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Tests\Fixtures\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ParentJob implements ShouldQueue
{
    use Batchable, Queueable;

    public function handle(): void
    {
        ChildJob::dispatch()->onConnection('database');
    }
}
