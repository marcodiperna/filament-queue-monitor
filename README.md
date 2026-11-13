# p2tinformatica/filament-queue-monitor

Code, job falliti e batch di Laravel nel pannello [Filament](https://filamentphp.com).

Legge le **tabelle standard** del driver database di Laravel — `jobs`, `failed_jobs`,
`job_batches` — senza tabelle aggiuntive, senza listener e senza migrazioni: quello che
vedi è quello che vede il worker.

| Pagina | Cosa mostra | Azioni |
|---|---|---|
| **Code** | job in attesa, in esecuzione e programmati, con tentativi e orari | elimina un job bloccato |
| **Job falliti** | job, coda, prima riga dell'errore; l'eccezione completa nel dettaglio | riprova (singolo, selezionati, tutti), elimina, svuota |
| **Batch** | avanzamento, job totali e falliti, stato | — |

Le voci di menu di Code e Job falliti mostrano un contatore quando non sono vuote.

## Requisiti

- PHP 8.2+
- Laravel 12 o 13
- Filament 5
- coda con driver `database` (`QUEUE_CONNECTION=database`)

## Installazione

```bash
composer require p2tinformatica/filament-queue-monitor
```

Nel pannello:

```php
use P2TInformatica\FilamentQueueMonitor\QueueMonitorPlugin;

->plugins([
    QueueMonitorPlugin::make()
        ->navigationGroup('Sistema')
        ->authorize(fn ($user) => $user?->can('gestire sistema')),
])
```

> Senza `authorize()` le pagine sono aperte a **chiunque entri nel pannello**. In
> produzione conviene sempre limitarle.

## Opzioni

| Metodo | Default | |
|---|---|---|
| `navigationGroup(string\|Closure\|null)` | nessuno | gruppo di menu |
| `navigationSort(int)` | `90` | posizione della prima voce; le altre seguono (+1, +2) |
| `navigationIcons(queued:, failed:, batches:)` | Heroicons | icone delle tre voci, anche solo alcune |
| `authorize(Closure)` | tutti | riceve l'utente collegato, restituisce `bool` |

## Traduzioni

Italiano e inglese inclusi; si segue `app.locale`. Per modificarle:

```bash
php artisan vendor:publish --tag=filament-queue-monitor-translations
```

## Venendo da moox/jobs

1. `composer remove moox/jobs`
2. togliere i quattro plugin `Jobs*Plugin` dal pannello e aggiungere `QueueMonitorPlugin`
3. eliminare `config/core.php` e `config/jobs.php` se pubblicati

Le tabelle di moox (`job_manager`, `job_batch_manager`, `job_queue_workers`) non servono
più: restano nel database finché una migrazione non le toglie.

## Test

```bash
composer install
composer test
```

I test girano su un'app Laravel di prova creata da
[Orchestra Testbench](https://packages.tools/testbench), con SQLite in memoria.
La CI li ripete su Laravel 12 e 13.

## Licenza

MIT
