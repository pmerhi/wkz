<?php

namespace App\Filament\Resources\CrawlSeiteResource\Pages;

use App\Filament\Resources\CrawlSeiteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCrawlSeite extends EditRecord
{
    protected static string $resource = CrawlSeiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
