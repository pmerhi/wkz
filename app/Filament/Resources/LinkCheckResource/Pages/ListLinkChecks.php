<?php

namespace App\Filament\Resources\LinkCheckResource\Pages;

use App\Filament\Resources\LinkCheckResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListLinkChecks extends ListRecords
{
    protected static string $resource = LinkCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pruefen')
                ->label('Links jetzt prüfen')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalDescription('Sammelt alle externen Links aus den Inhalten und prüft ihren Status. Das kann eine Minute dauern.')
                ->action(function () {
                    set_time_limit(0);
                    Artisan::call('links:check');
                    Notification::make()->title('Link-Prüfung abgeschlossen')
                        ->body(trim(Artisan::output()) ?: 'Fertig.')->success()->send();
                }),
            Actions\Action::make('pruefenMitDb')
                ->label('Inkl. DB-Links')
                ->icon('heroicon-o-circle-stack')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Zusätzlich alle Website-/Termin-Links der Zulassungsstellen prüfen (dauert deutlich länger).')
                ->action(function () {
                    set_time_limit(0);
                    Artisan::call('links:check', ['--mit-db' => true]);
                    Notification::make()->title('Link-Prüfung (inkl. DB) abgeschlossen')
                        ->body(trim(Artisan::output()) ?: 'Fertig.')->success()->send();
                }),
        ];
    }
}
