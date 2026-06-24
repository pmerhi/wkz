<?php

namespace App\Console\Commands;

use App\Models\Kreis;
use App\Models\KreisStatistik;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportKbaBestand extends Command
{
    protected $signature = 'kba:import {--path= : KBA-FZ1-XLSX oder normalisierte CSV} {--jahr= : Stand-Jahr} {--quelle=KBA FZ1 (DL-DE-BY-2.0)}';

    protected $description = 'Importiert Kfz-/Pkw-Bestand + Pkw-Dichte je Kreis aus der KBA-FZ1-XLSX (oder normalisierter CSV).';

    public function handle(): int
    {
        $path = $this->option('path');
        if (! $path || ! is_file($path)) {
            $this->error('Datei nicht gefunden: '.$path);
            return self::FAILURE;
        }

        $rows = str_ends_with(strtolower($path), '.xlsx')
            ? $this->leseFz1Xlsx($path)
            : $this->leseCsv($path);
        if (! $rows) {
            $this->error('Keine verwertbaren Zeilen (Header: ags;kfz_bestand;pkw_bestand;pkw_dichte;stand_jahr).');
            return self::FAILURE;
        }

        // Auf Kreis-AGS (5-stellig) aggregieren – FZ3 liefert 8-stellige Gemeinde-AGS.
        $agg = [];
        foreach ($rows as $r) {
            $ags = preg_replace('/\D/', '', (string) ($r['ags'] ?? ''));
            if (strlen($ags) < 5) {
                continue;
            }
            $kreisAgs = substr($ags, 0, 5);
            $agg[$kreisAgs] ??= ['kfz' => 0, 'pkw' => 0, 'dichte' => null, 'jahr' => null];
            $agg[$kreisAgs]['kfz'] += (int) ($r['kfz_bestand'] ?? 0);
            $agg[$kreisAgs]['pkw'] += (int) ($r['pkw_bestand'] ?? 0);
            // Dichte/Jahr nur direkt übernehmen, wenn schon auf Kreisebene geliefert (keine Aggregation sinnvoll).
            if (! empty($r['pkw_dichte'])) {
                $agg[$kreisAgs]['dichte'] = (float) str_replace(',', '.', (string) $r['pkw_dichte']);
            }
            if (! empty($r['stand_jahr'])) {
                $agg[$kreisAgs]['jahr'] = (int) $r['stand_jahr'];
            }
        }

        $jahrOpt = $this->option('jahr') ? (int) $this->option('jahr') : null;
        $quelle  = $this->option('quelle');
        $ok = 0;
        $miss = 0;

        foreach ($agg as $kreisAgs => $d) {
            $kreis = Kreis::where('ags', $kreisAgs)->first();
            if (! $kreis) {
                $miss++;
                continue;
            }

            $stat = KreisStatistik::firstOrNew(['kreis_id' => $kreis->id]);
            $stat->kfz_bestand = $d['kfz'] ?: $stat->kfz_bestand;
            $stat->pkw_bestand = $d['pkw'] ?: $stat->pkw_bestand;
            $stat->stand_jahr  = $d['jahr'] ?? $jahrOpt ?? $stat->stand_jahr;
            $stat->quelle      = $quelle;

            // Pkw-Dichte: gelieferte bevorzugen, sonst aus vorhandener Einwohnerzahl ableiten.
            if ($d['dichte']) {
                $stat->pkw_dichte = $d['dichte'];
            } elseif ($stat->einwohner && $stat->pkw_bestand) {
                $stat->pkw_dichte = round($stat->pkw_bestand / $stat->einwohner * 1000, 1);
            }

            $stat->save();
            $ok++;
        }

        $this->info("Kreise aktualisiert: {$ok} | AGS ohne passenden Kreis: {$miss}");

        return self::SUCCESS;
    }

    /**
     * Liest die KBA-FZ1-XLSX (Sheet FZ1.1). Spalten werden per Header-Text erkannt
     * (robust gegen Layout-Verschiebungen) und auf das normalisierte Schema gemappt.
     */
    private function leseFz1Xlsx(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        if (in_array('FZ1.1', $reader->listWorksheetNames($path), true)) {
            $reader->setLoadSheetsOnly('FZ1.1');
        }
        $raw = $reader->load($path)->getActiveSheet()->toArray(null, true, false, false);

        // Header steckt in zwei verbundenen Zeilen; die untere enthält "Kennziffer" zusammenhängend.
        $headerZeile = null;
        foreach ($raw as $i => $r) {
            if (str_contains(mb_strtolower(implode(' ', array_map('strval', $r))), 'kennziffer')) {
                $headerZeile = $i;
                break;
            }
        }
        if ($headerZeile === null) {
            return [];
        }

        $headerText = function (int $c) use ($raw, $headerZeile): string {
            $t = trim(($raw[$headerZeile - 1][$c] ?? '').' '.($raw[$headerZeile][$c] ?? ''));
            return mb_strtolower(preg_replace('/\s+/u', ' ', $t));
        };

        $finde = function (callable $pruef) use ($raw, $headerZeile, $headerText): ?int {
            for ($c = 0; $c < count($raw[$headerZeile]); $c++) {
                if ($pruef($headerText($c))) {
                    return $c;
                }
            }
            return null;
        };

        $colKennziffer = $finde(fn ($h) => str_contains($h, 'kennziffer'));
        $colPkw = $finde(fn ($h) => str_contains($h, 'personenkraftwagen insgesamt'));
        $colKfz = $finde(fn ($h) => str_contains($h, 'kraftfahrzeuge insgesamt') && ! str_contains($h, 'anhäng'));
        $colDichte = $finde(fn ($h) => str_contains($h, 'pkw-dichte'));

        if ($colKennziffer === null || $colPkw === null || $colKfz === null) {
            return [];
        }

        $rows = [];
        foreach (array_slice($raw, $headerZeile + 1, null, true) as $r) {
            $kennziffer = trim((string) ($r[$colKennziffer] ?? ''));
            if (! preg_match('/^(\d{5})/', $kennziffer, $m)) {
                continue;   // Land-/Summenzeilen ohne 5-stellige Kennziffer überspringen
            }
            $rows[] = [
                'ags'         => $m[1],
                'kfz_bestand' => (int) preg_replace('/\D/', '', (string) ($r[$colKfz] ?? '')),
                'pkw_bestand' => (int) preg_replace('/\D/', '', (string) ($r[$colPkw] ?? '')),
                'pkw_dichte'  => $colDichte !== null ? preg_replace('/[^\d,.]/', '', (string) ($r[$colDichte] ?? '')) : '',
            ];
        }

        return $rows;
    }

    /** Liest eine CSV mit Header (Trennzeichen ; oder , autoerkannt) als assoziative Zeilen. */
    private function leseCsv(string $path): array
    {
        $lines = array_filter(array_map('trim', file($path, FILE_IGNORE_NEW_LINES)));
        if (! $lines) {
            return [];
        }
        $first = reset($lines);
        $sep = substr_count($first, ';') >= substr_count($first, ',') ? ';' : ',';
        $header = array_map(fn ($h) => strtolower(trim($h)), str_getcsv($first, $sep));

        $rows = [];
        foreach (array_slice($lines, 1) as $line) {
            $cells = str_getcsv($line, $sep);
            if (count($cells) < count($header)) {
                continue;
            }
            $rows[] = array_combine($header, array_slice($cells, 0, count($header)));
        }

        return $rows;
    }
}
