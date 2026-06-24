<?php

namespace App\Filament\Resources\WusstestFaktResource\Pages;

use App\Filament\Resources\WusstestFaktResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWusstestFakts extends ListRecords
{
    protected static string $resource = WusstestFaktResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
