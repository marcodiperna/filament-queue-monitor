<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Support;

use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Js;
use MarcoDiPerna\FilamentQueueMonitor\QueueMonitorPlugin;

/**
 * A job's full error, readable, with a button that copies it for a ticket or
 * a chat. Only standard Filament components: the panel theme decides how it
 * looks.
 */
final class ErrorModal
{
    public static function action(string $attribute = 'exception'): Action
    {
        $action = Action::make('showError')
            ->label(__('filament-queue-monitor::queue-monitor.error.show'))
            ->icon(Heroicon::OutlinedExclamationCircle)
            ->color('danger')
            ->visible(fn (Model $record): bool => filled($record->getAttribute($attribute)))
            ->modalHeading(fn (Model $record): string => QueueMonitorPlugin::get()->formatJobName($record->getAttribute('name')))
            ->modalDescription(__('filament-queue-monitor::queue-monitor.columns.error'))
            ->modalIcon(Heroicon::OutlinedExclamationCircle)
            ->modalIconColor('danger')
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('filament-queue-monitor::queue-monitor.error.close'))
            ->schema([self::entry($attribute)])
            ->modalFooterActions(fn (Action $action, Model $record): array => [
                $action->getModalCancelAction(),
                self::copyAction((string) $record->getAttribute($attribute)),
            ]);

        return QueueMonitorPlugin::get()->modifyErrorModal($action);
    }

    /**
     * Copies to the clipboard in the browser, without a server round trip.
     */
    public static function copyAction(string $text): Action
    {
        return Action::make('copyError')
            ->label(__('filament-queue-monitor::queue-monitor.error.copy'))
            ->icon(Heroicon::OutlinedClipboardDocument)
            ->color('primary')
            ->alpineClickHandler('window.navigator.clipboard.writeText('.Js::from($text).'); $tooltip('.Js::from(__('filament-queue-monitor::queue-monitor.error.copied')).', { timeout: 1500 })');
    }

    public static function entry(string $attribute = 'exception', ?string $label = null): TextEntry
    {
        return TextEntry::make($attribute)
            ->label($label)
            ->hiddenLabel($label === null)
            ->columnSpanFull()
            ->copyable()
            ->copyableState(fn (?string $state): ?string => $state)
            ->copyMessage(__('filament-queue-monitor::queue-monitor.error.copied'))
            // A Markdown code block: monospaced, with the original line breaks
            // and the panel's "prose" style. No package styles, so theme and
            // colours stay the panel's own.
            ->markdown()
            ->formatStateUsing(fn (?string $state): string => "```\n".str_replace('```', "'''", (string) $state)."\n```");
    }
}
