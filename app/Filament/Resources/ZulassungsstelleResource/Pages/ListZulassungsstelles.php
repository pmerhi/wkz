<?php

namespace App\Filament\Resources\ZulassungsstelleResource\Pages;

use App\Filament\Resources\ZulassungsstelleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListZulassungsstelles extends ListRecords
{
    protected static string $resource = ZulassungsstelleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
