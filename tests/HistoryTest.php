<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Tests;

use Filament\Actions\Action;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use MarcoDiPerna\FilamentQueueMonitor\Models\JobBatch;
use MarcoDiPerna\FilamentQueueMonitor\Models\JobRun;
use MarcoDiPerna\FilamentQueueMonitor\Models\QueuedJob;
use MarcoDiPerna\FilamentQueueMonitor\QueueMonitorPlugin;
use MarcoDiPerna\FilamentQueueMonitor\Resources\FailedJobs\Pages\ListFailedJobs;
use MarcoDiPerna\FilamentQueueMonitor\Resources\JobRuns\JobRunResource;
use MarcoDiPerna\FilamentQueueMonitor\Resources\JobRuns\Pages\ListJobRuns;
use MarcoDiPerna\FilamentQueueMonitor\Support\Duration;
use MarcoDiPerna\FilamentQueueMonitor\Support\ErrorModal;
use MarcoDiPerna\FilamentQueueMonitor\Tests\Fixtures\CustomFailedJobResource;
use MarcoDiPerna\FilamentQueueMonitor\Tests\Fixtures\Jobs\ChildJob;
use MarcoDiPerna\FilamentQueueMonitor\Tests\Fixtures\Jobs\FailingJob;
use MarcoDiPerna\FilamentQueueMonitor\Tests\Fixtures\Jobs\ParentJob;
use MarcoDiPerna\FilamentQueueMonitor\Tests\Fixtures\Jobs\SucceedingJob;
use MarcoDiPerna\FilamentQueueMonitor\Widgets\HistoryOverview;
use PHPUnit\Framework\Attributes\Test;

/**
 * History, operator and stats, with real jobs run by a worker.
 */
class HistoryTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('queue-monitor.history.enabled', true);
        $app['config']->set('queue-monitor.operator.track', true);
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    private function work(int $jobs = 1): void
    {
        foreach (range(1, $jobs) as $ignored) {
            Artisan::call('queue:work', ['connection' => 'database', '--once' => true, '--stop-when-empty' => true]);
        }
    }

    #[Test]
    public function a_dispatched_job_carries_who_started_it(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        SucceedingJob::dispatch();

        $this->assertSame($admin->getKey(), QueuedJob::query()->first()->operator_id);
    }

    #[Test]
    public function every_run_is_recorded_with_its_outcome_and_duration(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        SucceedingJob::dispatch();
        FailingJob::dispatch();
        auth()->logout();

        $this->work(2);

        $runs = JobRun::query()->orderBy('id')->get();
        $this->assertSame([SucceedingJob::class, FailingJob::class], $runs->pluck('name')->all());
        $this->assertSame([JobRun::STATUS_SUCCEEDED, JobRun::STATUS_FAILED], $runs->pluck('status')->all());
        $this->assertNotNull($runs[0]->duration_ms);
        $this->assertStringContainsString('Stampante non raggiungibile', $runs[1]->exception);
        $this->assertSame([$admin->getKey(), $admin->getKey()], $runs->pluck('operator_id')->all());
    }

    #[Test]
    public function a_job_queued_by_a_job_inherits_the_operator(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        ParentJob::dispatch();
        auth()->logout();

        $this->work();

        $child = QueuedJob::query()->get()->first(fn (QueuedJob $job): bool => $job->name === ChildJob::class);
        $this->assertSame($admin->getKey(), $child->operator_id);
    }

    #[Test]
    public function a_batch_shows_who_started_it_through_the_history(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        Bus::batch([new SucceedingJob, new SucceedingJob])->name('Etichette')->dispatch();
        auth()->logout();

        $this->work(2);

        $this->assertSame($admin->getKey(), JobBatch::query()->first()->operator_id);
    }

    #[Test]
    public function the_operator_name_can_come_from_another_attribute(): void
    {
        config()->set('queue-monitor.operator.name_attribute', 'fullName');

        $admin = $this->admin();
        $admin->update(['surname' => 'Rossi']);
        $this->actingAs($admin);
        SucceedingJob::dispatch();
        $this->work();

        Livewire::test(ListJobRuns::class)->assertSee('Admin Rossi');
    }

    #[Test]
    public function the_history_page_and_its_stats_render(): void
    {
        $this->actingAs($this->admin());
        SucceedingJob::dispatch();
        FailingJob::dispatch();
        $this->work(2);

        QueueMonitorPlugin::get()->formatJobNameUsing(fn (string $name): string => 'Elaborazione '.class_basename($name));

        Livewire::test(ListJobRuns::class)
            ->assertOk()
            ->assertSee('Elaborazione SucceedingJob')
            ->assertSee('Riuscito')
            ->assertSee('Fallito')
            ->assertSee('Admin');

        Livewire::test(HistoryOverview::class)
            ->assertSee('Esecuzioni')
            ->assertSee('50,0%');

        $this->assertSame('Storico', JobRunResource::getNavigationLabel());
    }

    #[Test]
    public function the_error_opens_in_a_modal(): void
    {
        $this->actingAs($this->admin());
        FailingJob::dispatch();
        $this->work();

        $run = JobRun::query()->first();

        Livewire::test(ListJobRuns::class)
            ->mountAction(TestAction::make('showError')->table($run))
            ->assertSee('Stampante non raggiungibile');
    }

    #[Test]
    public function a_page_can_be_replaced_and_the_error_modal_reshaped(): void
    {
        $plugin = QueueMonitorPlugin::get()
            ->resource('failed', CustomFailedJobResource::class)
            ->modifyErrorModalUsing(fn (Action $action) => $action->modalHeading('Errore personalizzato'));

        $this->assertSame(CustomFailedJobResource::class, $plugin->getResource('failed'));
        $this->assertSame('Errore personalizzato', ErrorModal::action()->getModalHeading());

        $this->actingAs($this->admin());

        Livewire::test(ListFailedJobs::class)->assertSee('Tabella dell\'app');

        $this->expectException(\InvalidArgumentException::class);
        $plugin->resource('inesistente', \stdClass::class);
    }

    #[Test]
    public function old_runs_are_pruned(): void
    {
        JobRun::query()->create(['name' => 'Vecchio', 'status' => JobRun::STATUS_SUCCEEDED, 'started_at' => now()->subDays(8)]);
        JobRun::query()->create(['name' => 'Recente', 'status' => JobRun::STATUS_SUCCEEDED, 'started_at' => now()->subDay()]);

        Artisan::call('model:prune', ['--model' => [JobRun::class]]);

        $this->assertSame(['Recente'], JobRun::query()->pluck('name')->all());
    }

    #[Test]
    public function the_history_is_never_pruned_below_the_stats_period(): void
    {
        config(['queue-monitor.history.keep_days' => 3, 'queue-monitor.history.stats_days' => 14]);

        JobRun::query()->create(['name' => 'Dieci giorni fa', 'status' => JobRun::STATUS_SUCCEEDED, 'started_at' => now()->subDays(10)]);
        JobRun::query()->create(['name' => 'Venti giorni fa', 'status' => JobRun::STATUS_SUCCEEDED, 'started_at' => now()->subDays(20)]);

        Artisan::call('model:prune', ['--model' => [JobRun::class]]);

        $this->assertSame(['Dieci giorni fa'], JobRun::query()->pluck('name')->all());
    }

    #[Test]
    public function durations_read_like_a_person_would_say_them(): void
    {
        $this->assertSame('850 ms', Duration::format(850));
        $this->assertSame('4,2 s', Duration::format(4200));
        $this->assertSame('3 min 10 s', Duration::format(190_000));
        $this->assertSame('2 h 5 min', Duration::format(7_500_000));
        // How AVG and SUM come back from MySQL and MariaDB.
        $this->assertSame('1,5 s', Duration::format('1523.4000'));
        $this->assertSame('—', Duration::format(null));
    }
}
