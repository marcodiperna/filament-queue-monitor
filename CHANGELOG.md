# Changelog

## v1.1.0 — 2026-11-14

- **Storico** opzionale (`QUEUE_MONITOR_HISTORY`): ogni esecuzione con esito, durata,
  tentativo ed errore, nella tabella `queue_monitor_runs`, ripulita ogni giorno oltre
  `QUEUE_MONITOR_KEEP_DAYS` (7 giorni, quanti ne mostrano le statistiche). Pagina Storico con filtri.
- **Statistiche**: in Code quanti job aspettano e da quanto il più vecchio; nello Storico
  esecuzioni, falliti, durata media e tempo di lavoro delle ultime 24 ore, con l'andamento
  degli ultimi giorni (`QUEUE_MONITOR_STATS_DAYS`, 7 di default).
- **Operatore** opzionale (`QUEUE_MONITOR_TRACK_OPERATOR`): chi ha avviato il job, anche
  per i job accodati da altri job e per i batch.
- Plugin: `formatJobNameUsing()`, `formatOperatorUsing()`, `hideQueueColumn()`,
  `groupRecordActions()` (azioni di riga nel menu a tre puntini, attivo di default),
  `modifyErrorModalUsing()`, `resource()` per sostituire una pagina con una sottoclasse.
- L'errore si apre in una modale con il testo intero e il pulsante Copia; nessuno stile
  proprio, solo componenti standard di Filament.
- Il nome del job resta completo di namespace nei dati; a video si accorcia.

## v1.0.0 — 2026-11-13

Prima versione, nata in un gestionale dove ha sostituito `moox/jobs` (che non
supportava Laravel 13).

- Tre pagine: **Code** (job in attesa, in esecuzione, programmati), **Job
  falliti** (errore completo, riprova singola, multipla o totale, svuota) e
  **Batch** (avanzamento e falliti).
- Lettura diretta delle tabelle standard del driver database di Laravel, con
  nomi e connessioni presi da `config/queue.php`.
- Plugin configurabile: gruppo di menu, posizione, icone, autorizzazione.
- Testi in italiano e inglese.
- Laravel 12 e 13, Filament 5.
