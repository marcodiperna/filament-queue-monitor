<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Support;

use Filament\Tables\Columns\TextColumn;
use MarcoDiPerna\FilamentQueueMonitor\QueueMonitorPlugin;

/**
 * Columns shared by the pages, so a job or an operator reads the same
 * everywhere.
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
     * Who started the job. Empty when no user did: scheduler, commands,
     * other processes.
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
     * The error on one line: short class name and message. A click opens the
     * full text, with a button to copy it.
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
