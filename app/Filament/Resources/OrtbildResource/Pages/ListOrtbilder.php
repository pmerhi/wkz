<?php

namespace App\Filament\Resources\OrtbildResource\Pages;

use App\Filament\Resources\OrtbildResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListOrtbilder extends ListRecords
{
    protected static string $resource = OrtbildResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download')
                ->label('Auswahl herunterladen')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->requiresConfirmation()
                ->modalDescription('Lädt alle als Hero/Footer markierten Bilder lokal nach public/img/orte/.')
                ->action(function () {
                    Artisan::call('ortbilder:download');
                    Notification::make()->title('Download abgeschlossen')
                        ->body(trim(Artisan::output()))->success()->send();
                }),
        ];
    }
}
