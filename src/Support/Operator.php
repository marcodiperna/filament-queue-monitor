<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Support;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use MarcoDiPerna\FilamentQueueMonitor\QueueMonitorPlugin;

/**
 * Who started a job.
 *
 * The operator is captured at dispatch time, while the session still exists:
 * in the worker auth() is empty, so asking from inside the job would be too
 * late. The id goes into every job's payload without the jobs knowing.
 *
 * Jobs queued by a running job run in the worker, without a session: they
 * inherit their parent's operator, so a whole chain stays attributed to
 * whoever pressed the button.
 */
final class Operator
{
    /** The operator of the job the worker is running right now. */
    private static ?int $current = null;

    public static function enabled(): bool
    {
        return (bool) config('queue-monitor.operator.track', false);
    }

    public static function payloadKey(): string
    {
        return (string) config('queue-monitor.operator.payload_key', 'operator_id');
    }

    /**
     * @return class-string<Model>
     */
    public static function model(): string
    {
        return config('queue-monitor.operator.model')
            ?? config('auth.providers.users.model')
            ?? User::class;
    }

    public static function register(): void
    {
        Queue::createPayloadUsing(fn (): array => [
            self::payloadKey() => auth()->id() ?? self::$current,
        ]);

        Event::listen(JobProcessing::class, fn (JobProcessing $event) => self::$current = self::fromJob($event->job));

        // The operator applies to the running job and the children it queues:
        // once it ends it must be forgotten, or the worker's next job would be
        // attributed to someone unrelated.
        Event::listen(JobProcessed::class, fn () => self::$current = null);
        Event::listen(JobFailed::class, fn () => self::$current = null);
    }

    public static function fromJob(Job $job): ?int
    {
        return self::fromPayload($job->payload());
    }

    /**
     * @param  array<string, mixed>|string|null  $payload
     */
    public static function fromPayload(array|string|null $payload): ?int
    {
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }

        $id = $payload[self::payloadKey()] ?? null;

        return is_numeric($id) ? (int) $id : null;
    }

    /** @var array<int, string|null> */
    private static array $names = [];

    /**
     * The name to show for an id, or null if the user no longer exists.
     * A list often repeats the same people: each one is loaded once.
     */
    public static function name(?int $id): ?string
    {
        if ($id === null) {
            return null;
        }

        if (! array_key_exists($id, self::$names)) {
            $model = self::model();
            $user = $model::query()->find($id);

            self::$names[$id] = $user ? QueueMonitorPlugin::get()->formatOperator($user) : null;
        }

        return self::$names[$id];
    }
}
