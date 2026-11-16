<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MarcoDiPerna\FilamentQueueMonitor\Support\Operator;

/**
 * One run of a job: when it started, how long it took, how it ended and who
 * started it.
 */
class JobRun extends Model
{
    use MassPrunable;

    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    /** An exception, but the job will be retried. */
    public const STATUS_RETRIED = 'retried';

    public $timestamps = false;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return (string) config('queue-monitor.history.table', 'queue_monitor_runs');
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::model(), 'operator_id');
    }

    public function prunable(): Builder
    {
        return static::query()->where('started_at', '<', now()->subDays(static::keepDays()));
    }

    /**
     * How many days to keep: the configured ones, but never fewer than the
     * stats show, or the chart would have empty days.
     */
    public static function keepDays(): int
    {
        return max(
            (int) config('queue-monitor.history.keep_days', 7),
            static::statsDays(),
        );
    }

    public static function statsDays(): int
    {
        return max(1, (int) config('queue-monitor.history.stats_days', 7));
    }
}
