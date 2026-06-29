<?php

namespace App\Console\Commands;

use App\Models\KreisStatistik;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Importiert das Ladesäulenregister der Bundesnetzagentur (CSV, ;-getrennt,
 * Windows-1252) und aggregiert öffentliche Normal-/Schnellladepunkte je Kreis
 * (PLZ → Kreis über plz_gemeinde) in kreis_statistik. Quelle: bundesnetzagentur.de.
 */
class ImportLadesaeulen extends Command
{
    protected $signature = 'import:ladesaeulen {--path= : Pfad zur BNetzA-CSV} {--stand=2026-04 : Datenstand}';
    protected $description = 'Importiert Ladepunkte (Normal/Schnell) je Kreis aus dem BNetzA-Ladesäulenregister.';

    public function handle(): int
    {
        $path = $this->option('path');
        if (! $path || ! is_file($path)) {
            $this->error('CSV nicht gefunden: '.$path);
            return self::FAILURE;
        }

        // PLZ → Kreis-ID (erste Zuordnung je PLZ).
        $plzKreis = [];
        foreach (DB::table('plz_gemeinde')->whereNotNull('kreis_id')->get(['plz', 'kreis_id']) as $r) {
            $plzKreis[$r->plz] ??= $r->kreis_id;
        }
        // Fallback: normalisierter Kreis-Name → Kreis-ID.
        $nameKreis = [];
        foreach (\App\Models\Kreis::whereNotNull('name')->get(['id', 'name']) as $k) {
            $nameKreis[$this->normKreis($k->name)] ??= $k->id;
        }
        $this->info('PLZ→Kreis: '.count($plzKreis).' · Name→Kreis: '.count($nameKreis));

        $fh = fopen($path, 'r');
        // Bis zur Kopfzeile vorspulen.
        while (($row = fgetcsv($fh, 0, ';')) !== false) {
            if (($row[0] ?? '') === 'Ladeeinrichtungs-ID') {
                break;
            }
        }

        $agg = [];           // kreis_id => ['normal'=>x,'schnell'=>y]
        $zeilen = 0; $ohnePlz = 0;
        while (($row = fgetcsv($fh, 0, ';')) !== false) {
            if (count($row) < 12 || ($row[0] ?? '') === '') {
                continue;
            }
            $zeilen++;
            $art    = (string) ($row[4] ?? '');
            $anzahl = (int) ($row[5] ?? 0);
            $plz    = trim((string) ($row[11] ?? ''));
            if ($anzahl < 1) {
                $anzahl = 1;
            }
            $kreisId = $plzKreis[$plz] ?? null;
            if (! $kreisId) {
                // Fallback über den Kreis-Namen (Spalte 13, Windows-1252).
                $kreisName = mb_convert_encoding((string) ($row[13] ?? ''), 'UTF-8', 'Windows-1252');
                $kreisId = $nameKreis[$this->normKreis($kreisName)] ?? null;
            }
            if (! $kreisId) {
                $ohnePlz++;
                continue;
            }
            $agg[$kreisId] ??= ['normal' => 0, 'schnell' => 0];
            if (stripos($art, 'Schnell') !== false) {
                $agg[$kreisId]['schnell'] += $anzahl;
            } else {
                $agg[$kreisId]['normal'] += $anzahl;
            }
        }
        fclose($fh);

        $stand = (string) $this->option('stand');
        $aktualisiert = 0; $sumN = 0; $sumS = 0;
        foreach ($agg as $kreisId => $z) {
            $stat = KreisStatistik::firstOrNew(['kreis_id' => $kreisId]);
            $stat->ladepunkte_normal  = $z['normal'];
            $stat->ladepunkte_schnell = $z['schnell'];
            $stat->ladepunkte_stand   = $stand;
            $stat->save();
            $aktualisiert++; $sumN += $z['normal']; $sumS += $z['schnell'];
        }

        $this->info("Datenzeilen: $zeilen · ohne Kreis-Treffer: $ohnePlz");
        $this->info("Kreise aktualisiert: $aktualisiert · Normalladepunkte: ".number_format($sumN, 0, ',', '.')." · Schnellladepunkte: ".number_format($sumS, 0, ',', '.'));
        return self::SUCCESS;
    }

    /** Kreis-Name auf Vergleichsschlüssel normalisieren. */
    private function normKreis(string $s): string
    {
        $s = mb_strtolower(explode(',', $s)[0]);
        $s = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $s);
        $s = preg_replace('/\b(landkreis|kreisfreie stadt|stadtkreis|kreis|landeshauptstadt|hansestadt|stadt)\b/u', '', $s);

        return preg_replace('/[^a-z]/', '', $s);
    }
}
