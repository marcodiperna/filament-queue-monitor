<?php

declare(strict_types=1);

namespace P2TInformatica\FilamentQueueMonitor\Resources\Concerns;

use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;
use P2TInformatica\FilamentQueueMonitor\QueueMonitorPlugin;

/**
 * Quello che le tre risorse prendono dal plugin: accesso, gruppo, ordine e
 * icona. Nessuna compare nella ricerca globale e nessuna si crea a mano.
 */
trait BelongsToQueueMonitor
{
    abstract protected static function monitorKey(): string;

    abstract protected static function monitorOffset(): int;

    public static function canAccess(): bool
    {
        return QueueMonitorPlugin::get()->isAuthorized();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationGroup(): ?string
    {
        return QueueMonitorPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return QueueMonitorPlugin::get()->getNavigationSort() + static::monitorOffset();
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return QueueMonitorPlugin::get()->getNavigationIcon(static::monitorKey());
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-queue-monitor::queue-monitor.'.static::monitorKey().'.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('filament-queue-monitor::queue-monitor.'.static::monitorKey().'.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-queue-monitor::queue-monitor.'.static::monitorKey().'.plural');
    }

    public static function canGloballySearch(): bool
    {
        return false;
    }

    protected static function label(string $key): string
    {
        return __('filament-queue-monitor::queue-monitor.'.$key);
    }
}
