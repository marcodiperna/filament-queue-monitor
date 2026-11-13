# marcodiperna/filament-queue-monitor

Code, job falliti e batch di Laravel nel pannello [Filament](https://filamentphp.com).

Legge le **tabelle standard** del driver database di Laravel — `jobs`, `failed_jobs`,
`job_batches` — senza tabelle aggiuntive, senza listener e senza migrazioni: quello che
vedi è quello che vede il worker.

| Pagina | Cosa mostra | Azioni |
|---|---|---|
| **Code** | job in attesa, in esecuzione e programmati, con tentativi e orari | elimina un job bloccato |
| **Job falliti** | job, coda, prima riga dell'errore; l'eccezione completa nel dettaglio | riprova (singolo, selezionati, tutti), elimina, svuota |
| **Batch** | avanzamento, job totali e falliti, stato | — |
| **Storico** *(opzionale)* | ogni esecuzione: esito, durata, tentativo, errore | filtri per stato, job e giorno |

Le voci di menu di Code e Job falliti mostrano un contatore quando non sono vuote. Code e
Storico hanno in testa le statistiche: quanti job aspettano e da quanto il più vecchio; per
lo storico esecuzioni, falliti, durata media e tempo di lavoro delle ultime 24 ore, con
l'andamento degli ultimi giorni (7, configurabili).

## Requisiti

- PHP 8.2+
- Laravel 12 o 13
- Filament 5
- coda con driver `database` (`QUEUE_CONNECTION=database`)

## Installazione

```bash
composer require marcodiperna/filament-queue-monitor
```

Nel pannello:

```php
use MarcoDiPerna\FilamentQueueMonitor\QueueMonitorPlugin;

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
| `navigationIcons(queued:, failed:, batches:, history:)` | Heroicons | icone delle voci, anche solo alcune |
| `authorize(Closure)` | tutti | riceve l'utente collegato, restituisce `bool` |
| `formatJobNameUsing(Closure)` | classe senza namespace | riceve il nome completo del job (`$name`) |
| `formatOperatorUsing(Closure)` | attributo `name` | riceve il modello utente (`$user`) |
| `hideQueueColumn()` | visibile | per chi usa una coda sola |
| `groupRecordActions(bool)` | `true` | azioni di riga nel menu a tre puntini di Filament; `false` le mostra affiancate |
| `modifyErrorModalUsing(Closure)` | — | ritocca la modale dell'errore; riceve e restituisce l'`Action` |
| `resource(string $page, string $class)` | — | sostituisce una pagina (`queued`, `failed`, `batches`, `history`) con una sottoclasse dell'app |

## Storico e operatore

Due funzioni opzionali, che si attivano dalla configurazione e non dal plugin, perché
devono girare anche nel worker, dove i pannelli Filament non si caricano:

```dotenv
QUEUE_MONITOR_HISTORY=true          # una riga per ogni esecuzione, pagina Storico
QUEUE_MONITOR_KEEP_DAYS=7           # pulizia giornaliera delle righe più vecchie
QUEUE_MONITOR_STATS_DAYS=7          # giorni dell'andamento nelle statistiche
QUEUE_MONITOR_TRACK_OPERATOR=true   # chi ha avviato il job
```

**Storico.** Laravel cancella un job appena finisce: senza storico non si sa se il backup di
stanotte è girato. Attivandolo, il pacchetto crea la tabella `queue_monitor_runs` con il
normale `php artisan migrate` e la ripulisce ogni giorno (`model:prune` nello scheduler),
senza mai scendere sotto i giorni delle statistiche.

**Operatore.** L'id dell'utente collegato entra nel payload al momento del dispatch, quando la
sessione c'è ancora. I job accodati da un job in esecuzione ereditano l'operatore del padre,
così un'intera catena resta attribuita a chi ha premuto il bottone. Compare in Code, Job
falliti e Storico; nei batch, se lo storico è attivo. I job partiti senza utente (scheduler,
comandi) si leggono come *Sistema*.

Per personalizzare il resto (nome della chiave nel payload, modello utente, tabella):

```bash
php artisan vendor:publish --tag=filament-queue-monitor-config
php artisan vendor:publish --tag=filament-queue-monitor-migrations   # solo per modificare la tabella
```

## Personalizzare una pagina

Ogni pagina è una normale resource Filament: per cambiarne colonne, filtri o azioni si
estende e si registra al suo posto.

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

Il pacchetto non ha template né stili propri: usa solo componenti di Filament, quindi tema,
colori e personalizzazioni del pannello valgono anche qui.

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
più: restano nel database finché una migrazione non le toglie. Se avevi aggiunto a moox
l'utente che avvia il job, qui c'è già: `QUEUE_MONITOR_TRACK_OPERATOR=true`.

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
