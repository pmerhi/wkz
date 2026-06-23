<?php

namespace App\Filament\Resources\KonsolidierteStelleResource\Pages;

use App\Filament\Resources\KonsolidierteStelleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKonsolidierteStelles extends ListRecords
{
    protected static string $resource = KonsolidierteStelleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
