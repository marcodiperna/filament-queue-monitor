<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use MarcoDiPerna\FilamentQueueMonitor\Support\Operator;

/**
 * A queued job, from the database driver's table. The worker writes and
 * empties it: here it is only read.
 */
class QueuedJob extends Model
{
    public const STATE_RUNNING = 'running';

    public const STATE_SCHEDULED = 'scheduled';

    public const STATE_WAITING = 'waiting';

    public $timestamps = false;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return (string) config('queue.connections.database.table', 'jobs');
    }

    public function getConnectionName(): ?string
    {
        return config('queue.connections.database.connection') ?? parent::getConnectionName();
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => static::displayName($this->payload));
    }

    protected function state(): Attribute
    {
        return Attribute::get(fn (): string => match (true) {
            $this->reserved_at !== null => self::STATE_RUNNING,
            $this->available_at > now()->timestamp => self::STATE_SCHEDULED,
            default => self::STATE_WAITING,
        });
    }

    protected function queuedAt(): Attribute
    {
        return Attribute::get(fn (): Carbon => static::time($this->created_at));
    }

    protected function availableFrom(): Attribute
    {
        return Attribute::get(fn (): Carbon => static::time($this->available_at));
    }

    /**
     * The name Laravel stores in the payload, usually the fully qualified
     * class. The plugin decides how to show it (formatJobName).
     */
    public static function displayName(?string $payload): string
    {
        $data = json_decode((string) $payload, true);

        return (string) ($data['displayName'] ?? '?');
    }

    protected function operatorId(): Attribute
    {
        return Attribute::get(fn (): ?int => Operator::fromPayload($this->payload));
    }

    /**
     * The queue's time columns are Unix timestamps: they are read in the
     * application's timezone.
     */
    public static function time(int|string $timestamp): Carbon
    {
        return Carbon::createFromTimestamp((int) $timestamp, config('app.timezone'));
    }
}
