<?php

namespace App\Filament\Resources\BundeslandResource\Pages;

use App\Filament\Resources\BundeslandResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBundesland extends EditRecord
{
    protected static string $resource = BundeslandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
