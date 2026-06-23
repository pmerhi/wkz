<?php

namespace App\Filament\Resources\KategorieResource\Pages;

use App\Filament\Resources\KategorieResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKategories extends ListRecords
{
    protected static string $resource = KategorieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
