<?php

namespace App\Filament\Resources\EnrichmentIdeaResource\Pages;

use App\Filament\Resources\EnrichmentIdeaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEnrichmentIdea extends EditRecord
{
    protected static string $resource = EnrichmentIdeaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
