<?php

namespace App\Filament\Resources\CrawlSeiteResource\Pages;

use App\Filament\Resources\CrawlSeiteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCrawlSeites extends ListRecords
{
    protected static string $resource = CrawlSeiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
