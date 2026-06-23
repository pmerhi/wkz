<?php

namespace App\Filament\Resources\KennzeichenKuerzelResource\Pages;

use App\Filament\Resources\KennzeichenKuerzelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKennzeichenKuerzels extends ListRecords
{
    protected static string $resource = KennzeichenKuerzelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
