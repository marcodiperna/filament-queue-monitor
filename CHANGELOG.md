# Changelog

## v1.0.0 — 2026-11-13

Prima versione, estratta da P2TGest dove ha sostituito `moox/jobs` (che non
supportava Laravel 13).

- Tre pagine: **Code** (job in attesa, in esecuzione, programmati), **Job
  falliti** (errore completo, riprova singola, multipla o totale, svuota) e
  **Batch** (avanzamento e falliti).
- Lettura diretta delle tabelle standard del driver database di Laravel, con
  nomi e connessioni presi da `config/queue.php`.
- Plugin configurabile: gruppo di menu, posizione, icone, autorizzazione.
- Testi in italiano e inglese.
- Laravel 12 e 13, Filament 5.
