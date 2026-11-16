# marcodiperna/filament-queue-monitor

**English** · [Italiano](README.it.md)

Laravel queues, failed jobs and batches in your [Filament](https://filamentphp.com) panel.

It reads Laravel's **standard tables** for the database driver — `jobs`, `failed_jobs`,
`job_batches`: what you see is what the worker sees. On top of them it keeps a run history,
on by default, in a table of its own; operator tracking is opt-in.

| Page | Shows | Actions |
|---|---|---|
| **Queue** | waiting, running and scheduled jobs, with attempts and times | delete a stuck job |
| **Failed jobs** | job, queue, first line of the error; the full exception on click | retry (one, selected, all), delete, flush |
| **Batches** | progress, total and failed jobs, state | — |
| **History** | every run: outcome, duration, attempt, error | filters by state, job and day |

The Queue and Failed jobs menu items show a counter when not empty. Queue and History have
stats on top: how many jobs are waiting and for how long the oldest has been; for the
history, runs, failures, average duration and working time over the last 24 hours, with the
trend of the last days (7, configurable).

## Requirements

- PHP 8.2+
- Laravel 12 or 13
- Filament 5
- the `database` queue driver (`QUEUE_CONNECTION=database`)

## Installation

```bash
composer require marcodiperna/filament-queue-monitor
```

In your panel:

```php
use MarcoDiPerna\FilamentQueueMonitor\QueueMonitorPlugin;

->plugins([
    QueueMonitorPlugin::make()
        ->navigationGroup('System')
        ->authorize(fn ($user) => $user?->can('manage system')),
])
```

> Without `authorize()` the pages are open to **anyone who can enter the panel**. In
> production, always restrict them.

Not using batches? Show only the pages you need:

```php
QueueMonitorPlugin::make()->pages(['queued', 'failed', 'history'])
```

## Options

| Method | Default | |
|---|---|---|
| `navigationGroup(string\|Closure\|null)` | none | menu group |
| `navigationSort(int)` | `90` | position of the first item; the others follow (+1, +2, +3) |
| `pages(array)` | all | which pages to show: `queued`, `failed`, `batches`, `history` (the latter only with the history enabled) |
| `navigationIcons(queued:, failed:, batches:, history:)` | Heroicons | menu icons, all or some |
| `authorize(Closure)` | everyone | receives the logged-in user, returns `bool` |
| `formatJobNameUsing(Closure)` | class without namespace | receives the full job name (`$name`) |
| `formatOperatorUsing(Closure)` | `operator.name_attribute` (`name`) | receives the user model (`$user`) |
| `hideQueueColumn()` | visible | for apps with a single queue |
| `groupRecordActions(bool)` | `true` | row actions in Filament's three-dots menu; `false` shows them inline |
| `modifyErrorModalUsing(Closure)` | — | reshapes the error modal; receives and returns the `Action` |
| `resource(string $page, string $class)` | — | replaces a page (`queued`, `failed`, `batches`, `history`) with your own subclass |

## History and operator

Two features driven by the configuration rather than the plugin, because they must run in
the worker too, where Filament panels are not loaded:

```dotenv
QUEUE_MONITOR_HISTORY=true          # default; one row per run, History page
QUEUE_MONITOR_KEEP_DAYS=7           # daily cleanup of older rows
QUEUE_MONITOR_STATS_DAYS=7          # days in the stats trend
QUEUE_MONITOR_TRACK_OPERATOR=true   # who started the job
```

**History.** Laravel deletes a job as soon as it finishes: without a history there is no way
to know whether last night's backup ran. The package creates the `queue_monitor_runs` table
through your usual `php artisan migrate` and prunes it daily (`model:prune` in the
scheduler), never below the stats period. `QUEUE_MONITOR_HISTORY=false` turns it off, along
with its table and page.

**Operator.** The logged-in user's id goes into the payload at dispatch time, while the
session still exists. Jobs queued by a running job inherit their parent's operator, so a
whole chain stays attributed to whoever pressed the button. It shows on Queue, Failed jobs
and History; on Batches when the history is enabled. Jobs started without a user (scheduler,
commands) read as *System*.

The name comes from the `name` attribute, which Laravel always has. If your users also have a
surname, `operator.name_attribute` in the configuration names the column or accessor to show
(for example `fullName`). For anything more elaborate there is `formatOperatorUsing()`.

```php
// config/queue-monitor.php
'operator' => [
    // ...
    'name_attribute' => 'fullName',
],
```

To customise the rest (payload key, user model, name attribute, table name):

```bash
php artisan vendor:publish --tag=filament-queue-monitor-config
php artisan vendor:publish --tag=filament-queue-monitor-migrations   # only to change the table
```

## Customising a page

Every page is a plain Filament resource: to change its columns, filters or actions, extend
it and register yours instead.

```php
use MarcoDiPerna\FilamentQueueMonitor\Resources\FailedJobs\FailedJobResource;

class FailedJobs extends FailedJobResource
{
    public static function table(Table $table): Table
    {
        return parent::table($table)->defaultPaginationPageOption(50);
    }
}

QueueMonitorPlugin::make()->resource('failed', FailedJobs::class)
```

The package ships no templates or styles of its own: it only uses Filament components, so
your panel's theme, colours and customisations apply here too.

## Translations

English and Italian included; `app.locale` decides. To change them:

```bash
php artisan vendor:publish --tag=filament-queue-monitor-translations
```

## Coming from moox/jobs

1. `composer remove moox/jobs`
2. remove the four `Jobs*Plugin` from the panel and add `QueueMonitorPlugin`
3. delete `config/core.php` and `config/jobs.php` if published

moox's tables (`job_manager`, `job_batch_manager`, `job_queue_workers`) are no longer needed:
they stay in the database until a migration drops them. If you had added to moox the user who
starts a job, it is built in here: `QUEUE_MONITOR_TRACK_OPERATOR=true`.

## Tests

```bash
composer install
composer test
```

Tests run on a throwaway Laravel app created by
[Orchestra Testbench](https://packages.tools/testbench), with in-memory SQLite. CI repeats
them on Laravel 12 and 13.

## License

MIT
