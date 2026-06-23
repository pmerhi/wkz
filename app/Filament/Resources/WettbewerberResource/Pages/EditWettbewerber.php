<?php

namespace App\Filament\Resources\WettbewerberResource\Pages;

use App\Filament\Resources\WettbewerberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWettbewerber extends EditRecord
{
    protected static string $resource = WettbewerberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
