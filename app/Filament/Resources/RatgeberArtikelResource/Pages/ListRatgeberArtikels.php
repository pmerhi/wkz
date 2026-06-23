<?php

namespace App\Filament\Resources\RatgeberArtikelResource\Pages;

use App\Filament\Resources\RatgeberArtikelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRatgeberArtikels extends ListRecords
{
    protected static string $resource = RatgeberArtikelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
