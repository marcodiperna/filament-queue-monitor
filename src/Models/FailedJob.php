<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use MarcoDiPerna\FilamentQueueMonitor\Support\Operator;

/**
 * Un job fallito, dalla tabella dei falliti configurata in queue.failed.
 */
class FailedJob extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return (string) config('queue.failed.table', 'failed_jobs');
    }

    public function getConnectionName(): ?string
    {
        return config('queue.failed.database') ?? parent::getConnectionName();
    }

    protected function casts(): array
    {
        return [
            'failed_at' => 'datetime',
        ];
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => QueuedJob::displayName($this->payload));
    }

    protected function operatorId(): Attribute
    {
        return Attribute::get(fn (): ?int => Operator::fromPayload($this->payload));
    }

    /**
     * La prima riga dell'eccezione: il messaggio, senza lo stack.
     */
    protected function error(): Attribute
    {
        return Attribute::get(fn (): string => Str::of((string) $this->exception)->before("\n")->limit(200)->toString());
    }
}
