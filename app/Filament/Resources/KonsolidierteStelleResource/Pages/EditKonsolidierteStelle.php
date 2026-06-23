<?php

namespace App\Filament\Resources\KonsolidierteStelleResource\Pages;

use App\Filament\Resources\KonsolidierteStelleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKonsolidierteStelle extends EditRecord
{
    protected static string $resource = KonsolidierteStelleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
