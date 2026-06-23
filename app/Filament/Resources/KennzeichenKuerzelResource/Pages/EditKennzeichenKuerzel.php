<?php

namespace App\Filament\Resources\KennzeichenKuerzelResource\Pages;

use App\Filament\Resources\KennzeichenKuerzelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKennzeichenKuerzel extends EditRecord
{
    protected static string $resource = KennzeichenKuerzelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
