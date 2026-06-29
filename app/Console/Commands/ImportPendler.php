<?php

namespace App\Console\Commands;

use App\Models\Kreis;
use App\Models\KreisStatistik;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Importiert Pendlerdaten (Auspendler-/Einpendlerquote, Pendlersaldo) je Kreis aus
 * dem offenen Pendleratlas der statistischen Ämter. Nur Kreis-Ebene (ARS endet auf
 * 0000000 → kreisfreie Städte/Stadtstaaten). Quelle: pendleratlas.statistikportal.de.
 */
class ImportPendler extends Command
{
    protected $signature = 'import:pendler {--jahr=2024}';
    protected $description = 'Importiert Pendlerdaten je Kreis aus dem Pendleratlas (statistische Ämter).';

    public function handle(): int
    {
        $jahr = (string) $this->option('jahr');
        $base = "https://pendleratlas.statistikportal.de/data/csv/$jahr/{$jahr}_";

        $saldo = $this->ladeMap($base.'Saldo_Karte_L00.csv');
        $ausp  = $this->ladeMap($base.'AUSP_Quote_Karte_L00.csv');
        $eip   = $this->ladeMap($base.'EIP_Quote_Karte_L00.csv');
        if ($saldo === null || $ausp === null) {
            $this->error('Pendleratlas-CSV nicht erreichbar (Jahr '.$jahr.'?).');
            return self::FAILURE;
        }
        $this->info('Kreis-Zeilen: Saldo='.count($saldo).' · Auspendlerquote='.count($ausp).' · Einpendlerquote='.count($eip ?? []));

        $kreisIdByAgs = Kreis::whereNotNull('ags')->pluck('id', 'ags');
        $agsListe = collect(array_keys($saldo + $ausp + ($eip ?? [])))->unique();

        $n = 0;
        foreach ($agsListe as $ags) {
            $kreisId = $kreisIdByAgs[$ags] ?? null;
            if (! $kreisId) {
                continue;
            }
            $stat = KreisStatistik::firstOrNew(['kreis_id' => $kreisId]);
            if (isset($ausp[$ags]))  $stat->auspendler_quote = $ausp[$ags];
            if (isset($eip[$ags]))   $stat->einpendler_quote = $eip[$ags];
            if (isset($saldo[$ags])) $stat->pendler_saldo = $saldo[$ags];
            $stat->pendler_stand = $jahr;
            $stat->save();
            $n++;
        }

        $this->info("Kreise mit Pendlerdaten aktualisiert: $n");
        return self::SUCCESS;
    }

    /** CSV laden → [Kreis-AGS(5) => Wert] nur für Kreis-Ebene (ARS …0000000). */
    private function ladeMap(string $url): ?array
    {
        try {
            $res = Http::withHeaders(['User-Agent' => 'WunschkennzeichenPortal/1.0'])->timeout(60)->get($url);
            if (! $res->successful()) {
                return null;
            }
        } catch (\Throwable $e) {
            return null;
        }
        $map = [];
        foreach (preg_split('/\r\n|\n|\r/', $res->body()) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, 'ARS')) {
                continue;
            }
            $p = explode(';', $line);
            $ars = $p[0] ?? '';
            if (strlen($ars) !== 12 || substr($ars, 5) !== '0000000') {
                continue;   // nur Kreis-Ebene
            }
            $wert = str_replace(',', '.', trim($p[1] ?? ''));
            if ($wert === '') {
                continue;
            }
            $map[substr($ars, 0, 5)] = is_numeric($wert) ? (str_contains($wert, '.') ? (float) $wert : (int) $wert) : null;
        }

        return $map;
    }
}
