<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Tests\Fixtures\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FailingJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 1;

    public function handle(): void
    {
        throw new \RuntimeException('Stampante non raggiungibile');
    }
}
