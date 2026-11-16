<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;
use MarcoDiPerna\FilamentQueueMonitor\QueueMonitorPlugin;

/**
 * A panel like a real application's: the plugin limited to whoever has the
 * administrator's email.
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->plugins([
                QueueMonitorPlugin::make()
                    ->navigationGroup('Sistema')
                    ->authorize(fn ($user): bool => $user?->email === 'admin@example.test'),
            ]);
    }
}
