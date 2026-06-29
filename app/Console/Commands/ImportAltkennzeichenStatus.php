<?php

namespace App\Console\Commands;

use App\Models\AltkennzeichenStatus;
use Illuminate\Console\Command;

class ImportAltkennzeichenStatus extends Command
{
    protected $signature = 'altkennzeichen:status-import {--path= : JSON-Datei der alten Infografik-Daten} {--frisch : Tabelle vorher leeren}';

    protected $description = 'Übernimmt die vollständigen Status-Daten der alten Altkennzeichen-Grafik (inkl. abgelehnter/beantragter) in unsere DB';

    public function handle(): int
    {
        $path = $this->option('path') ?: database_path('data/altkennzeichen-infografik.json');
        if (! is_file($path)) {
            $this->error('Datei nicht gefunden: '.$path);
            return self::FAILURE;
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            $this->error('JSON konnte nicht gelesen werden.');
            return self::FAILURE;
        }

        if ($this->option('frisch')) {
            AltkennzeichenStatus::truncate();
            $this->line('Tabelle geleert.');
        }

        $anzahl = 0;
        foreach ($data as $kgs => $eintrag) {
            $kreisname  = $eintrag['kreisname'] ?? null;
            $bundesland = $eintrag['bundesland'] ?? null;

            // kennzeichen kann Objekt (keyed) oder Array sein (Sonderfälle BN/GG).
            $kz = $eintrag['kennzeichen'] ?? [];
            $liste = array_is_list($kz) ? $kz : array_values($kz);

            foreach ($liste as $z) {
                $kuerzel = $z['kuerzel'] ?? null;
                if ($kuerzel === null || $kuerzel === '') {
                    continue;
                }
                AltkennzeichenStatus::updateOrCreate(
                    ['kgs' => (string) $kgs, 'kuerzel' => (string) $kuerzel],
                    [
                        'kreisname'   => $kreisname !== '' ? $kreisname : null,
                        'bundesland'  => $bundesland !== '' ? $bundesland : null,
                        'status'      => (int) ($z['status'] ?? 0),
                        'in_klammern' => (bool) ($z['in_klammern'] ?? false),
                    ]
                );
                $anzahl++;
            }
        }

        $this->info('Altkennzeichen-Status importiert: '.$anzahl.' Einträge in '.count($data).' Kreisen/KGS.');
        $this->line('Tipp: danach `php artisan infografik:daten` ausführen, um die Karte zu aktualisieren.');

        return self::SUCCESS;
    }
}
