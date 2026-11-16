<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Support;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use MarcoDiPerna\FilamentQueueMonitor\Models\JobRun;
use MarcoDiPerna\FilamentQueueMonitor\Models\QueuedJob;
use Throwable;

/**
 * Writes the history: one row when the worker picks up a job, completed when
 * it succeeds, fails or is going to be retried.
 */
final class RunRecorder
{
    /**
     * Runs opened by this worker: row id and start time, per job.
     *
     * @var array<string, array{id: int, start: float}>
     */
    private static array $open = [];

    public static function register(): void
    {
        Event::listen(JobProcessing::class, fn (JobProcessing $event) => self::start($event->job, $event->connectionName));
        Event::listen(JobProcessed::class, fn (JobProcessed $event) => self::finish($event->job, JobRun::STATUS_SUCCEEDED));
        Event::listen(JobExceptionOccurred::class, fn (JobExceptionOccurred $event) => $event->job->hasFailed()
            ? null
            : self::finish($event->job, JobRun::STATUS_RETRIED, $event->exception));
        Event::listen(JobFailed::class, fn (JobFailed $event) => self::finish($event->job, JobRun::STATUS_FAILED, $event->exception));
    }

    private static function start(Job $job, string $connection): void
    {
        $payload = $job->payload();

        $run = JobRun::query()->create([
            'job_uuid' => $job->uuid(),
            'name' => QueuedJob::displayName(json_encode($payload)),
            'queue' => $job->getQueue(),
            'connection' => $connection,
            'status' => JobRun::STATUS_RUNNING,
            'attempt' => $job->attempts(),
            'operator_id' => Operator::fromPayload($payload),
            'batch_id' => self::batchId($payload),
            'started_at' => now(),
        ]);

        self::$open[self::key($job)] = ['id' => $run->getKey(), 'start' => microtime(true)];
    }

    private static function finish(Job $job, string $status, ?Throwable $exception = null): void
    {
        $open = self::$open[self::key($job)] ?? null;

        if ($open === null) {
            return;
        }

        unset(self::$open[self::key($job)]);

        JobRun::query()->whereKey($open['id'])->update([
            'status' => $status,
            'finished_at' => now(),
            'duration_ms' => (int) round((microtime(true) - $open['start']) * 1000),
            'exception' => $exception ? Str::limit(get_class($exception).': '.$exception->getMessage(), 2000) : null,
        ]);
    }

    private static function key(Job $job): string
    {
        return ($job->uuid() ?? $job->getJobId()).'#'.$job->attempts();
    }

    /**
     * The batch id sits in the serialized command: it is read with a regular
     * expression instead of unserializing, because a batch can pass through
     * here hundreds of thousands of times.
     *
     * @param  array<string, mixed>  $payload
     */
    private static function batchId(array $payload): ?string
    {
        $command = (string) ($payload['data']['command'] ?? '');

        return preg_match('/batchId";s:36:"([0-9a-f-]{36})"/', $command, $found) ? $found[1] : null;
    }
}
