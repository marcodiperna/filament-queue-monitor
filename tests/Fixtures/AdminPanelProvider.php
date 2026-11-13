<?php

declare(strict_types=1);

namespace P2TInformatica\FilamentQueueMonitor\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;
use P2TInformatica\FilamentQueueMonitor\QueueMonitorPlugin;

/**
 * Un pannello come quello di un'applicazione vera: il plugin limitato a chi
 * ha l'email dell'amministratore.
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
