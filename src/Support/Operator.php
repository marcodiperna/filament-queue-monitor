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
 * Chi ha avviato un job.
 *
 * L'operatore si cattura al dispatch, quando la sessione c'e' ancora: nel
 * worker auth() e' vuota, quindi chiederlo dentro il job arriverebbe tardi.
 * L'id finisce nel payload di ogni job senza che i job debbano saperne niente.
 *
 * I job accodati da un job in esecuzione girano nel worker, senza sessione:
 * ereditano l'operatore del padre, cosi' un'intera catena resta attribuita a
 * chi ha premuto il bottone.
 */
final class Operator
{
    /** L'operatore del job che il worker sta eseguendo adesso. */
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

        // L'operatore vale per il job in corso e per i figli che accoda: appena
        // finisce va dimenticato, o il prossimo job del worker si ritroverebbe
        // attribuito a chi non c'entra.
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
     * Il nome da mostrare per un id, o null se l'utente non esiste piu'.
     * Un elenco ripete spesso le stesse persone: ognuna si legge una volta.
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
