<?php

namespace App\Filament\Resources\ZulassungsstelleResource\Pages;

use App\Filament\Resources\ZulassungsstelleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditZulassungsstelle extends EditRecord
{
    protected static string $resource = ZulassungsstelleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
