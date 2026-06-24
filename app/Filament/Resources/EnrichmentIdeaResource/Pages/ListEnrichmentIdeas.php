<?php

namespace App\Filament\Resources\EnrichmentIdeaResource\Pages;

use App\Filament\Resources\EnrichmentIdeaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEnrichmentIdeas extends ListRecords
{
    protected static string $resource = EnrichmentIdeaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
