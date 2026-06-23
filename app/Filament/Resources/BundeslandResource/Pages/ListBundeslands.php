<?php

namespace App\Filament\Resources\BundeslandResource\Pages;

use App\Filament\Resources\BundeslandResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBundeslands extends ListRecords
{
    protected static string $resource = BundeslandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
