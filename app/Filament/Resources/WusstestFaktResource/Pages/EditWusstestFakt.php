<?php

namespace App\Filament\Resources\WusstestFaktResource\Pages;

use App\Filament\Resources\WusstestFaktResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWusstestFakt extends EditRecord
{
    protected static string $resource = WusstestFaktResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
