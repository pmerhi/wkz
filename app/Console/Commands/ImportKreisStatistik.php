<?php

namespace App\Console\Commands;

use App\Models\Gemeinde;
use App\Models\Kreis;
use App\Models\KreisStatistik;
use Illuminate\Console\Command;

class ImportKreisStatistik extends Command
{
    protected $signature = 'kreis:statistik {--path= : JSON-Datei mit Statistik-Einträgen}';

    protected $description = 'Importiert/aktualisiert Regional-Statistik je Kreis (gequellt) aus einer JSON-Datei.';

    public function handle(): int
    {
        $path = $this->option('path') ?: database_path('data/kreis_statistik.json');
        if (! is_file($path)) {
            $this->error('Datei nicht gefunden: '.$path);
            return self::FAILURE;
        }

        $eintraege = json_decode((string) file_get_contents($path), true);
        if (! is_array($eintraege)) {
            $this->error('Ungültiges JSON.');
            return self::FAILURE;
        }

        $ok = 0;
        $skip = 0;
        foreach ($eintraege as $e) {
            $kreisId = $this->kreisId($e);
            if (! $kreisId) {
                $this->warn('Kein Kreis gefunden für: '.json_encode($e['stadt'] ?? $e['kreis_ags'] ?? $e));
                $skip++;
                continue;
            }

            $daten = array_filter([
                'einwohner'   => $e['einwohner'] ?? null,
                'flaeche_km2' => $e['flaeche_km2'] ?? null,
                'kfz_bestand' => $e['kfz_bestand'] ?? null,
                'pkw_bestand' => $e['pkw_bestand'] ?? null,
                'stand_jahr'  => $e['stand_jahr'] ?? null,
                'quelle'      => $e['quelle'] ?? null,
            ], fn ($v) => $v !== null);

            // Pkw-Dichte ableiten, wenn nicht geliefert.
            if (! isset($e['pkw_dichte']) && ! empty($daten['pkw_bestand']) && ! empty($daten['einwohner'])) {
                $daten['pkw_dichte'] = round($daten['pkw_bestand'] / $daten['einwohner'] * 1000, 1);
            } elseif (isset($e['pkw_dichte'])) {
                $daten['pkw_dichte'] = $e['pkw_dichte'];
            }

            KreisStatistik::updateOrCreate(['kreis_id' => $kreisId], $daten);
            $ok++;
        }

        $this->info("Statistik importiert/aktualisiert: {$ok} | übersprungen: {$skip}");

        return self::SUCCESS;
    }

    /** Löst die kreis_id über AGS oder Gemeindename (kreisfreie Stadt) auf. */
    private function kreisId(array $e): ?int
    {
        if (! empty($e['kreis_ags'])) {
            return Kreis::where('ags', $e['kreis_ags'])->value('id');
        }
        if (! empty($e['stadt'])) {
            return Gemeinde::where('name', $e['stadt'])->value('kreis_id');
        }
        return null;
    }
}
