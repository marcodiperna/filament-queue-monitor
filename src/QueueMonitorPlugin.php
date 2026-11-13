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

    protected ?Closure $formatJobNameUsing = null;

    protected ?Closure $formatOperatorUsing = null;

    protected bool $showQueueColumn = true;

    protected bool $groupRecordActions = true;

    protected ?Closure $modifyErrorModalUsing = null;

    /**
     * Le risorse registrate nel pannello. Un'app puo' sostituirne una con una
     * sua sottoclasse per cambiarne colonne, filtri o azioni.
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
     * La posizione della prima voce; le altre seguono.
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

    /**
     * Come mostrare il nome di un job. Riceve il nome salvato da Laravel, di
     * solito la classe completa; senza, si mostra la classe senza namespace.
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
     * Come mostrare l'operatore che ha avviato il job. Riceve il modello
     * utente; senza, si usa il suo attributo name.
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
     * Nasconde la colonna della coda: chi usa una coda sola leggerebbe la
     * stessa parola su ogni riga.
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
     * Le azioni di riga in un menu (i tre puntini di Filament), come di solito
     * nei pannelli; false le mostra come pulsanti affiancati.
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
     * Sostituisce una delle pagine con una sottoclasse dell'app:
     * queued, failed, batches o history.
     *
     *     ->resource('failed', App\\Filament\\FailedJobs::class)
     */
    public function resource(string $page, string $resource): static
    {
        if (! array_key_exists($page, $this->resources)) {
            throw new \InvalidArgumentException("Pagina sconosciuta: {$page}. Valori ammessi: ".implode(', ', array_keys($this->resources)).'.');
        }

        $this->resources[$page] = $resource;

        return $this;
    }

    public function getResource(string $page): string
    {
        return $this->resources[$page];
    }

    /**
     * Ritocca la modale dell'errore: larghezza, pulsanti, testi.
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
