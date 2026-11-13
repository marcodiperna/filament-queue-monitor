<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor;

use BackedEnum;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use MarcoDiPerna\FilamentQueueMonitor\Resources\FailedJobs\FailedJobResource;
use MarcoDiPerna\FilamentQueueMonitor\Resources\JobBatches\JobBatchResource;
use MarcoDiPerna\FilamentQueueMonitor\Resources\QueuedJobs\QueuedJobResource;

/**
 * Code, job falliti e batch nel pannello, letti dalle tabelle standard del
 * driver database di Laravel.
 *
 *     ->plugins([
 *         QueueMonitorPlugin::make()
 *             ->navigationGroup('Sistema')
 *             ->authorize(fn ($user) => $user?->can('gestire sistema')),
 *     ])
 *
 * Senza authorize() le pagine sono aperte a chi entra nel pannello.
 */
class QueueMonitorPlugin implements Plugin
{
    use EvaluatesClosures;

    protected string|Closure|null $navigationGroup = null;

    protected int $navigationSort = 90;

    protected ?Closure $authorizeUsing = null;

    /**
     * @var array{queued: string|BackedEnum|Htmlable, failed: string|BackedEnum|Htmlable, batches: string|BackedEnum|Htmlable}
     */
    protected array $navigationIcons = [
        'queued' => Heroicon::OutlinedQueueList,
        'failed' => Heroicon::OutlinedExclamationTriangle,
        'batches' => Heroicon::OutlinedRectangleStack,
    ];

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static */
        return filament(app(static::class)->getId());
    }

    public function getId(): string
    {
        return 'queue-monitor';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            QueuedJobResource::class,
            FailedJobResource::class,
            JobBatchResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}

    public function navigationGroup(string|Closure|null $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): ?string
    {
        return $this->evaluate($this->navigationGroup);
    }

    /**
     * La posizione della prima voce; le altre due seguono.
     */
    public function navigationSort(int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function getNavigationSort(): int
    {
        return $this->navigationSort;
    }

    public function navigationIcons(
        string|BackedEnum|Htmlable|null $queued = null,
        string|BackedEnum|Htmlable|null $failed = null,
        string|BackedEnum|Htmlable|null $batches = null,
    ): static {
        $this->navigationIcons = [
            'queued' => $queued ?? $this->navigationIcons['queued'],
            'failed' => $failed ?? $this->navigationIcons['failed'],
            'batches' => $batches ?? $this->navigationIcons['batches'],
        ];

        return $this;
    }

    public function getNavigationIcon(string $page): string|BackedEnum|Htmlable
    {
        return $this->navigationIcons[$page];
    }

    /**
     * Chi puo' vedere le pagine. Riceve l'utente collegato.
     */
    public function authorize(Closure $callback): static
    {
        $this->authorizeUsing = $callback;

        return $this;
    }

    public function isAuthorized(): bool
    {
        if ($this->authorizeUsing === null) {
            return true;
        }

        return (bool) $this->evaluate($this->authorizeUsing, [
            'user' => auth()->user(),
        ]);
    }
}
