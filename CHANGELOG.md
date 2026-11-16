# Changelog

## Unreleased

- Plugin: `pages()` chooses which pages to show, for example without Batches for apps
  that don't use them.
- The operator's name comes from the attribute set in `operator.name_attribute`
  (`name` by default), for users with separate first and last names and an accessor
  that joins them.
- Operator names are cached per request instead of per process: a worker or an Octane
  app no longer shows a stale name after a change.
- Code comments, error messages and the package description are now in English.

## v1.1.0 — 2026-11-14

- Optional **history** (`QUEUE_MONITOR_HISTORY`): every run with outcome, duration,
  attempt and error, in the `queue_monitor_runs` table, pruned daily after
  `QUEUE_MONITOR_KEEP_DAYS` (7 days, as many as the stats show). History page with filters.
- **Stats**: on Queue, how many jobs are waiting and for how long the oldest has been; on
  History, runs, failures, average duration and working time over the last 24 hours, with
  the trend of the last days (`QUEUE_MONITOR_STATS_DAYS`, 7 by default).
- Optional **operator** (`QUEUE_MONITOR_TRACK_OPERATOR`): who started the job, also for
  jobs queued by other jobs and for batches.
- Plugin: `formatJobNameUsing()`, `formatOperatorUsing()`, `hideQueueColumn()`,
  `groupRecordActions()` (row actions in the three-dots menu, on by default),
  `modifyErrorModalUsing()`, `resource()` to replace a page with a subclass.
- The error opens in a modal with the full text and a Copy button; no custom styles,
  only standard Filament components.
- The job name keeps its full namespace in the data; it is shortened on screen.

## v1.0.0 — 2026-11-13

First release, born in a business application where it replaced `moox/jobs` (which did
not support Laravel 13).

- Three pages: **Queue** (waiting, running and scheduled jobs), **Failed jobs** (full
  error, retry one, selected or all, flush) and **Batches** (progress and failures).
- Reads Laravel's standard database driver tables directly, with names and connections
  taken from `config/queue.php`.
- Configurable plugin: menu group, sort order, icons, authorization.
- Italian and English translations.
- Laravel 12 and 13, Filament 5.
