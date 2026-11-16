<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A group of jobs dispatched together, from the table configured in
 * queue.batching.
 */
class JobBatch extends Model
{
    public const STATE_RUNNING = 'running';

    public const STATE_DONE = 'done';

    public const STATE_DONE_WITH_FAILURES = 'done_with_failures';

    public const STATE_CANCELLED = 'cancelled';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    public function getTable(): string
    {
        return (string) config('queue.batching.table', 'job_batches');
    }

    public function getConnectionName(): ?string
    {
        return config('queue.batching.database') ?? parent::getConnectionName();
    }

    protected function progress(): Attribute
    {
        return Attribute::get(fn (): int => $this->total_jobs > 0
            ? (int) round(($this->total_jobs - $this->pending_jobs) / $this->total_jobs * 100)
            : 100);
    }

    protected function state(): Attribute
    {
        return Attribute::get(fn (): string => match (true) {
            $this->cancelled_at !== null => self::STATE_CANCELLED,
            $this->finished_at !== null && $this->failed_jobs > 0 => self::STATE_DONE_WITH_FAILURES,
            $this->finished_at !== null => self::STATE_DONE,
            default => self::STATE_RUNNING,
        });
    }

    protected function startedAt(): Attribute
    {
        return Attribute::get(fn (): Carbon => QueuedJob::time($this->created_at));
    }

    protected function endedAt(): Attribute
    {
        return Attribute::get(fn (): ?Carbon => $this->finished_at ? QueuedJob::time($this->finished_at) : null);
    }

    /**
     * Who started the batch: the operator of its first run. It comes from the
     * history, so Laravel's job_batches table stays untouched.
     */
    protected function operatorId(): Attribute
    {
        return Attribute::get(fn (): ?int => config('queue-monitor.history.enabled')
            ? JobRun::query()->where('batch_id', $this->getKey())->whereNotNull('operator_id')->orderBy('id')->value('operator_id')
            : null);
    }
}
