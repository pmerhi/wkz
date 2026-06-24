<?php

namespace App\Console\Commands;

use App\Models\Kreis;
use App\Models\KreisStatistik;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportDestatisKreise extends Command
{
    protected $signature = 'destatis:kreise {--path= : Destatis 04-kreise.xlsx} {--jahr=2024}';

    protected $description = 'Importiert Einwohner und Fläche je Kreis aus dem amtlichen Gemeindeverzeichnis (Destatis).';

    public function handle(): int
    {
        ini_set('memory_limit', '512M');

        $path = $this->option('path');
        if (! $path || ! is_file($path)) {
            $this->error('Datei nicht gefunden: '.$path);
            return self::FAILURE;
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        foreach ($reader->listWorksheetNames($path) as $name) {
            if (str_contains($name, 'Kreis')) {
                $reader->setLoadSheetsOnly($name);
                break;
            }
        }
        // Nur die ersten Spalten lesen (die Tabelle meldet sonst einen riesigen Spaltenbereich).
        $rows = $reader->load($path)->getActiveSheet()->rangeToArray('A1:F1000', null, true, false, false);

        $jahr = (int) $this->option('jahr');
        $ok = 0;
        $miss = 0;

        foreach ($rows as $r) {
            $digits = preg_replace('/\D/', '', (string) ($r[0] ?? ''));
            if (strlen($digits) < 4 || strlen($digits) > 5) {
                continue;   // nur Kreis-Zeilen (Bundesland=2, Reg.-Bez.=3, Insgesamt=leer überspringen)
            }
            $ags = str_pad($digits, 5, '0', STR_PAD_LEFT);
            $flaeche = (float) str_replace(',', '.', (string) ($r[4] ?? ''));
            $einw = (int) preg_replace('/\D/', '', (string) ($r[5] ?? ''));
            if (! $einw) {
                continue;
            }

            $kreis = Kreis::where('ags', $ags)->first();
            if (! $kreis) {
                $miss++;
                continue;
            }

            $stat = KreisStatistik::firstOrNew(['kreis_id' => $kreis->id]);
            $stat->einwohner = $einw;
            if ($flaeche > 0) {
                $stat->flaeche_km2 = $flaeche;
            }
            // Pkw-Dichte mit amtlicher Einwohnerzahl neu/exakt berechnen, wenn Pkw-Bestand vorliegt.
            if ($stat->pkw_bestand) {
                $stat->pkw_dichte = round($stat->pkw_bestand / $einw * 1000, 1);
            }
            $stat->quelle = 'KBA (Kfz/Pkw) · Destatis '.$jahr.' (Einwohner/Fläche)';
            $stat->save();
            $ok++;
        }

        $this->info("Kreise mit Einwohner/Fläche: {$ok} | AGS ohne Kreis-Treffer: {$miss}");

        return self::SUCCESS;
    }
}
