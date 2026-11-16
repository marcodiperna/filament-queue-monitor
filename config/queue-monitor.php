<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Run history
    |--------------------------------------------------------------------------
    |
    | Laravel deletes a job from the queue as soon as it finishes: without a
    | history there is no way to know whether last night's backup ran. When
    | enabled, every run leaves a row in queue_monitor_runs, pruned daily
    | after keep_days: seven days are enough for the stats, which cover the
    | last week.
    |
    */

    'history' => [
        'enabled' => env('QUEUE_MONITOR_HISTORY', false),
        'keep_days' => (int) env('QUEUE_MONITOR_KEEP_DAYS', 7),
        // Days in the History stats trend. The history is never pruned below
        // this threshold.
        'stats_days' => (int) env('QUEUE_MONITOR_STATS_DAYS', 7),
        'table' => 'queue_monitor_runs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Who started the job
    |--------------------------------------------------------------------------
    |
    | The logged-in user's id goes into the payload at dispatch time, while
    | the session still exists: in the worker auth() is empty. Jobs queued by
    | a running job inherit their parent's operator.
    |
    | The displayed name comes from name_attribute: name always exists, but
    | if users also have a surname, set the column or accessor that joins
    | them (full_name, fullName).
    |
    */

    'operator' => [
        'track' => env('QUEUE_MONITOR_TRACK_OPERATOR', false),
        'payload_key' => 'operator_id',
        'model' => null,
        'name_attribute' => 'name',
    ],

];
