<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Support;

use Filament\Tables\Columns\TextColumn;
use MarcoDiPerna\FilamentQueueMonitor\QueueMonitorPlugin;

/**
 * Le colonne che le pagine hanno in comune, cosi' un job o un operatore si
 * leggono allo stesso modo ovunque.
 */
final class Columns
{
    public static function jobName(string $attribute = 'name'): TextColumn
    {
        return TextColumn::make($attribute)
            ->label(__('filament-queue-monitor::queue-monitor.columns.job'))
            ->weight('bold')
            ->formatStateUsing(fn (?string $state): string => QueueMonitorPlugin::get()->formatJobName($state))
            ->tooltip(fn (?string $state): ?string => $state);
    }

    public static function queue(): TextColumn
    {
        return TextColumn::make('queue')
            ->label(__('filament-queue-monitor::queue-monitor.columns.queue'))
            ->badge()
            ->color('gray')
            ->visible(fn (): bool => QueueMonitorPlugin::get()->showsQueueColumn());
    }

    /**
     * Chi ha avviato il job. Vuota se il job non e' partito da un utente:
     * schedulatore, comandi, altri processi.
     */
    public static function operator(): TextColumn
    {
        return TextColumn::make('operator_id')
            ->label(__('filament-queue-monitor::queue-monitor.columns.operator'))
            ->formatStateUsing(fn (?int $state): ?string => Operator::name($state))
            ->placeholder(__('filament-queue-monitor::queue-monitor.system'))
            ->visible(fn (): bool => Operator::enabled());
    }

    /**
     * L'errore in una riga: nome breve della classe e messaggio. Il clic apre
     * il testo intero, con il pulsante per copiarlo.
     */
    public static function error(string $attribute = 'exception'): TextColumn
    {
        return TextColumn::make($attribute)
            ->label(__('filament-queue-monitor::queue-monitor.columns.error'))
            ->color('danger')
            ->formatStateUsing(fn (?string $state): ?string => self::shortError($state))
            ->limit(90)
            ->wrap()
            ->placeholder('—')
            ->action(ErrorModal::action());
    }

    public static function shortError(?string $exception): ?string
    {
        if ($exception === null) {
            return null;
        }

        $firstLine = strtok($exception, "\n") ?: $exception;

        return preg_replace('/^[\\\\\\w]+\\\\(\\w+):/', '$1:', $firstLine);
    }
}
