<?php

namespace App\Filament\Resources\ExtraktZulassungsstelleResource\Pages;

use App\Filament\Resources\ExtraktZulassungsstelleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExtraktZulassungsstelle extends EditRecord
{
    protected static string $resource = ExtraktZulassungsstelleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
