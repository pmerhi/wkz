<?php

namespace App\Filament\Resources\OrtbildResource\Pages;

use App\Filament\Resources\OrtbildResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrtbild extends EditRecord
{
    protected static string $resource = OrtbildResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /** Nach dem Speichern: ausgewähltes Bild ohne lokale Datei automatisch laden. */
    protected function afterSave(): void
    {
        $bild = $this->record;
        if (in_array($bild->rolle, \App\Models\Ortbild::AUSGEWAEHLT) && ! $bild->src) {
            $bild->herunterladen();
        }
    }
}
