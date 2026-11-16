<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor;

use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use MarcoDiPerna\FilamentQueueMonitor\Resources\FailedJobs\FailedJobResource;
use MarcoDiPerna\FilamentQueueMonitor\Resources\JobBatches\JobBatchResource;
use MarcoDiPerna\FilamentQueueMonitor\Resources\JobRuns\JobRunResource;
use MarcoDiPerna\FilamentQueueMonitor\Resources\QueuedJobs\QueuedJobResource;

/**
 * Queue, failed jobs and batches in the panel, read from the standard tables
 * of Laravel's database driver.
 *
 *     ->plugins([
 *         QueueMonitorPlugin::make()
 *             ->navigationGroup('System')
 *             ->authorize(fn ($user) => $user?->can('manage system')),
 *     ])
 *
 * Without authorize() the pages are open to anyone who can enter the panel.
 */
class QueueMonitorPlugin implements Plugin
{
    use EvaluatesClosures;

    protected string|Closure|null $navigationGroup = null;

    protected int $navigationSort = 90;

    protected ?Closure $authorizeUsing = null;

    protected ?Closure $formatJobNameUsing = null;

    protected ?Closure $formatOperatorUsing = null;

    protected bool $showQueueColumn = true;

    protected bool $groupRecordActions = true;

    protected ?Closure $modifyErrorModalUsing = null;

    /**
     * The resources registered in the panel. An app can replace one with its
     * own subclass to change columns, filters or actions.
     *
     * @var array{queued: class-string, failed: class-string, batches: class-string, history: class-string}
     */
    protected array $resources = [
        'queued' => QueuedJobResource::class,
        'failed' => FailedJobResource::class,
        'batches' => JobBatchResource::class,
        'history' => JobRunResource::class,
    ];

    /**
     * @var array{queued: string|BackedEnum|Htmlable, failed: string|BackedEnum|Htmlable, batches: string|BackedEnum|Htmlable, history: string|BackedEnum|Htmlable}
     */
    protected array $navigationIcons = [
        'queued' => Heroicon::OutlinedQueueList,
        'failed' => Heroicon::OutlinedExclamationTriangle,
        'batches' => Heroicon::OutlinedRectangleStack,
        'history' => Heroicon::OutlinedClock,
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
        $panel->resources(array_values(array_filter([
            $this->resources['queued'],
            $this->resources['failed'],
            $this->resources['batches'],
            config('queue-monitor.history.enabled') ? $this->resources['history'] : null,
        ])));
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
     * The position of the first item; the others follow.
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
        string|BackedEnum|Htmlable|null $history = null,
    ): static {
        $this->navigationIcons = [
            'queued' => $queued ?? $this->navigationIcons['queued'],
            'failed' => $failed ?? $this->navigationIcons['failed'],
            'batches' => $batches ?? $this->navigationIcons['batches'],
            'history' => $history ?? $this->navigationIcons['history'],
        ];

        return $this;
    }

    public function getNavigationIcon(string $page): string|BackedEnum|Htmlable
    {
        return $this->navigationIcons[$page];
    }

    /**
     * Who can see the pages. Receives the logged-in user.
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

    /**
     * How to show a job's name. Receives the name Laravel stored, usually the
     * fully qualified class; by default the class without namespace is shown.
     */
    public function formatJobNameUsing(Closure $callback): static
    {
        $this->formatJobNameUsing = $callback;

        return $this;
    }

    public function formatJobName(?string $name): string
    {
        $name = (string) $name;

        return $this->formatJobNameUsing
            ? (string) $this->evaluate($this->formatJobNameUsing, ['name' => $name, 'state' => $name])
            : class_basename($name);
    }

    /**
     * How to show the operator who started the job. Receives the user model;
     * by default its name attribute is used.
     */
    public function formatOperatorUsing(Closure $callback): static
    {
        $this->formatOperatorUsing = $callback;

        return $this;
    }

    public function formatOperator(Model $user): string
    {
        return $this->formatOperatorUsing
            ? (string) $this->evaluate($this->formatOperatorUsing, ['user' => $user, 'record' => $user])
            : (string) ($user->getAttribute('name') ?? $user->getKey());
    }

    /**
     * Hides the queue column: with a single queue it would repeat the same
     * word on every row.
     */
    public function hideQueueColumn(bool $condition = true): static
    {
        $this->showQueueColumn = ! $condition;

        return $this;
    }

    public function showsQueueColumn(): bool
    {
        return $this->showQueueColumn;
    }

    /**
     * Row actions in a menu (Filament's three dots), as panels usually do;
     * false shows them as inline buttons.
     */
    public function groupRecordActions(bool $condition = true): static
    {
        $this->groupRecordActions = $condition;

        return $this;
    }

    /**
     * @param  array<int, Action>  $actions
     * @return array<int, Action|ActionGroup>
     */
    public function recordActions(array $actions): array
    {
        return $this->groupRecordActions ? [ActionGroup::make($actions)] : $actions;
    }

    /**
     * Replaces one of the pages with a subclass from the app:
     * queued, failed, batches or history.
     *
     *     ->resource('failed', App\\Filament\\FailedJobs::class)
     */
    public function resource(string $page, string $resource): static
    {
        if (! array_key_exists($page, $this->resources)) {
            throw new \InvalidArgumentException("Unknown page: {$page}. Allowed values: ".implode(', ', array_keys($this->resources)).'.');
        }

        $this->resources[$page] = $resource;

        return $this;
    }

    public function getResource(string $page): string
    {
        return $this->resources[$page];
    }

    /**
     * Reshapes the error modal: width, buttons, texts.
     *
     *     ->modifyErrorModalUsing(fn (Action $action) => $action->modalWidth('3xl'))
     */
    public function modifyErrorModalUsing(Closure $callback): static
    {
        $this->modifyErrorModalUsing = $callback;

        return $this;
    }

    public function modifyErrorModal(Action $action): Action
    {
        return $this->modifyErrorModalUsing
            ? ($this->evaluate($this->modifyErrorModalUsing, ['action' => $action]) ?? $action)
            : $action;
    }
}
