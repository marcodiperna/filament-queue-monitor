<?php

declare(strict_types=1);

namespace P2TInformatica\FilamentQueueMonitor\Tests;

use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use P2TInformatica\FilamentQueueMonitor\Models\FailedJob;
use P2TInformatica\FilamentQueueMonitor\Models\JobBatch;
use P2TInformatica\FilamentQueueMonitor\Models\QueuedJob;
use P2TInformatica\FilamentQueueMonitor\QueueMonitorPlugin;
use P2TInformatica\FilamentQueueMonitor\Resources\FailedJobs\FailedJobResource;
use P2TInformatica\FilamentQueueMonitor\Resources\FailedJobs\Pages\ListFailedJobs;
use P2TInformatica\FilamentQueueMonitor\Resources\JobBatches\JobBatchResource;
use P2TInformatica\FilamentQueueMonitor\Resources\JobBatches\Pages\ListJobBatches;
use P2TInformatica\FilamentQueueMonitor\Resources\QueuedJobs\Pages\ListQueuedJobs;
use P2TInformatica\FilamentQueueMonitor\Resources\QueuedJobs\QueuedJobResource;
use PHPUnit\Framework\Attributes\Test;

class QueueMonitorTest extends TestCase
{
    private function payload(string $class): string
    {
        return json_encode(['displayName' => $class, 'job' => 'Illuminate\\Queue\\CallQueuedHandler@call', 'data' => []]);
    }

    private function queued(array $attributes = []): void
    {
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => $this->payload('App\\Jobs\\ProcessDatabaseBackup'),
            'attempts' => 0,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
            ...$attributes,
        ]);
    }

    private function failed(): FailedJob
    {
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => $this->payload('App\\Jobs\\SendDigest'),
            'exception' => "TransportException: Connessione SMTP rifiutata\n#0 stack",
            'failed_at' => now(),
        ]);

        return FailedJob::query()->latest('id')->first();
    }

    #[Test]
    public function the_plugin_registers_three_resources_in_the_chosen_group(): void
    {
        $this->actingAs($this->admin());

        $this->assertSame('Sistema', QueuedJobResource::getNavigationGroup());
        $this->assertSame([90, 91, 92], [
            QueuedJobResource::getNavigationSort(),
            FailedJobResource::getNavigationSort(),
            JobBatchResource::getNavigationSort(),
        ]);
        $this->assertSame('Job falliti', FailedJobResource::getNavigationLabel());
        $this->assertSame('queue-monitor', QueueMonitorPlugin::get()->getId());
    }

    #[Test]
    public function the_queue_shows_jobs_by_name_and_state(): void
    {
        $this->actingAs($this->admin());
        $this->queued();
        $this->queued(['reserved_at' => now()->timestamp]);
        $this->queued(['available_at' => now()->addHour()->timestamp]);

        $this->assertSame(
            [QueuedJob::STATE_WAITING, QueuedJob::STATE_RUNNING, QueuedJob::STATE_SCHEDULED],
            QueuedJob::query()->orderBy('id')->get()->pluck('state')->all(),
        );

        Livewire::test(ListQueuedJobs::class)
            ->assertOk()
            ->assertSee('ProcessDatabaseBackup')
            ->assertSee('In esecuzione')
            ->assertSee('Programmato');

        $this->assertSame('3', QueuedJobResource::getNavigationBadge());
    }

    #[Test]
    public function the_authorize_callback_closes_the_pages(): void
    {
        $this->actingAs($this->operator());

        $this->assertFalse(QueuedJobResource::canAccess());
        $this->assertFalse(FailedJobResource::canAccess());
        $this->assertFalse(JobBatchResource::canAccess());
    }

    #[Test]
    public function without_authorize_every_panel_user_gets_in(): void
    {
        QueueMonitorPlugin::get()->authorize(fn (): bool => true);
        $this->actingAs($this->operator());

        $this->assertTrue(QueuedJobResource::canAccess());
    }

    #[Test]
    public function a_failed_job_shows_its_error_and_goes_back_in_the_queue(): void
    {
        $this->actingAs($this->admin());
        $job = $this->failed();

        Livewire::test(ListFailedJobs::class)
            ->assertSee('SendDigest')
            ->assertSee('Connessione SMTP rifiutata')
            ->callAction(TestAction::make('retry')->table($job))
            ->assertHasNoActionErrors();

        $this->assertNull(FailedJob::query()->find($job->getKey()));
        $this->assertSame(1, QueuedJob::query()->count());
    }

    #[Test]
    public function selected_failed_jobs_are_retried_together(): void
    {
        $this->actingAs($this->admin());
        $jobs = collect([$this->failed(), $this->failed()]);

        Livewire::test(ListFailedJobs::class)
            ->selectTableRecords($jobs->map->getKey()->all())
            ->callAction(TestAction::make('retrySelected')->table()->bulk());

        $this->assertSame(0, FailedJob::query()->count());
        $this->assertSame(2, QueuedJob::query()->count());
    }

    #[Test]
    public function failed_jobs_are_retried_all_or_flushed(): void
    {
        $this->actingAs($this->admin());
        $this->failed();
        $this->failed();

        Livewire::test(ListFailedJobs::class)->callAction('retryAll');
        $this->assertSame(2, QueuedJob::query()->count());

        $this->failed();
        Livewire::test(ListFailedJobs::class)->callAction('flush');
        $this->assertSame(0, FailedJob::query()->count());
    }

    #[Test]
    public function batches_show_their_progress(): void
    {
        $this->actingAs($this->admin());
        DB::table('job_batches')->insert([
            'id' => (string) Str::uuid(),
            'name' => 'Sincronizzazione contatti',
            'total_jobs' => 4,
            'pending_jobs' => 1,
            'failed_jobs' => 0,
            'failed_job_ids' => '[]',
            'created_at' => now()->timestamp,
        ]);

        $batch = JobBatch::query()->first();
        $this->assertSame(75, $batch->progress);
        $this->assertSame(JobBatch::STATE_RUNNING, $batch->state);

        Livewire::test(ListJobBatches::class)->assertSee('Sincronizzazione contatti')->assertSee('In esecuzione');
    }
}
