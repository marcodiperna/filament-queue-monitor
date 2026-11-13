<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storico delle esecuzioni
    |--------------------------------------------------------------------------
    |
    | Laravel cancella un job dalla coda appena finisce: senza storico non c'e'
    | modo di sapere se il backup di stanotte e' girato. Attivandolo, ogni
    | esecuzione lascia una riga in queue_monitor_runs, ripulita ogni giorno
    | oltre keep_days: sette giorni bastano alle statistiche, che guardano l'ultima settimana.
    |
    */

    'history' => [
        'enabled' => env('QUEUE_MONITOR_HISTORY', false),
        'keep_days' => (int) env('QUEUE_MONITOR_KEEP_DAYS', 7),
        // I giorni dell'andamento nelle statistiche dello Storico. Lo storico
        // non viene mai ripulito sotto questa soglia.
        'stats_days' => (int) env('QUEUE_MONITOR_STATS_DAYS', 7),
        'table' => 'queue_monitor_runs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Chi ha avviato il job
    |--------------------------------------------------------------------------
    |
    | L'id dell'utente collegato entra nel payload al momento del dispatch,
    | quando la sessione c'e' ancora: nel worker auth() e' vuota. I job accodati
    | da un job in esecuzione ereditano l'operatore del padre.
    |
    */

    'operator' => [
        'track' => env('QUEUE_MONITOR_TRACK_OPERATOR', false),
        'payload_key' => 'operator_id',
        'model' => null,
    ],

];
