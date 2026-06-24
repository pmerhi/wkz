<?php

namespace App\Console\Commands;

use App\Models\Kreis;
use App\Models\KreisStatistik;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportKbaElektro extends Command
{
    protected $signature = 'kba:elektro {--path= : KBA-FZ1-XLSX (Sheet FZ1.2)}';

    protected $description = 'Importiert die Zahl der Elektro-Pkw (BEV) je Kreis aus dem KBA-FZ1-Sheet FZ1.2.';

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
        if (! in_array('FZ1.2', $reader->listWorksheetNames($path), true)) {
            $this->error('Sheet FZ1.2 nicht gefunden.');
            return self::FAILURE;
        }
        $reader->setLoadSheetsOnly('FZ1.2');
        $raw = $reader->load($path)->getActiveSheet()->rangeToArray('A1:BB500', null, true, false, false);

        // Spalte mit "Kennziffer" in den (mehrzeiligen) Kopfzeilen finden.
        $ncol = count($raw[0] ?? $raw[1] ?? []);
        $colKennziffer = null;
        for ($c = 0; $c < $ncol && $colKennziffer === null; $c++) {
            for ($i = 0; $i < min(14, count($raw)); $i++) {
                if (str_contains(mb_strtolower((string) ($raw[$i][$c] ?? '')), 'kennziffer')) {
                    $colKennziffer = $c;
                    break;
                }
            }
        }
        if ($colKennziffer === null) {
            return self::FAILURE;
        }

        // Erste Datenzeile = erste 5-stellige Kennziffer in dieser Spalte.
        $dataStart = null;
        foreach ($raw as $i => $r) {
            if (preg_match('/^\d{5}/', trim((string) ($r[$colKennziffer] ?? '')))) {
                $dataStart = $i;
                break;
            }
        }
        if ($dataStart === null) {
            return self::FAILURE;
        }

        // Kombinierter Spaltenkopf aus allen Zeilen oberhalb der Daten → Elektro-(BEV)-Spalte.
        $colElektro = null;
        for ($c = 0; $c < $ncol; $c++) {
            $h = '';
            for ($i = 0; $i < $dataStart; $i++) {
                $h .= ' '.($raw[$i][$c] ?? '');
            }
            $h = mb_strtolower($h);
            if (str_contains($h, 'elektro') && str_contains($h, 'bev')) {
                $colElektro = $c;
                break;
            }
        }
        if ($colElektro === null) {
            $this->error('Elektro-(BEV)-Spalte nicht gefunden.');
            return self::FAILURE;
        }

        $agg = [];
        foreach (array_slice($raw, $dataStart) as $r) {
            if (! preg_match('/^(\d{5})/', trim((string) ($r[$colKennziffer] ?? '')), $m)) {
                continue;
            }
            $agg[$m[1]] = ($agg[$m[1]] ?? 0) + (int) preg_replace('/\D/', '', (string) ($r[$colElektro] ?? ''));
        }

        $ok = 0;
        $miss = 0;
        foreach ($agg as $ags => $bev) {
            $kreis = Kreis::where('ags', $ags)->first();
            if (! $kreis) {
                $miss++;
                continue;
            }
            $stat = KreisStatistik::firstOrNew(['kreis_id' => $kreis->id]);
            $stat->elektro_pkw = $bev;
            $stat->save();
            $ok++;
        }

        $this->info("Elektro-Pkw je Kreis gesetzt: {$ok} | AGS ohne Kreis-Treffer: {$miss}");

        return self::SUCCESS;
    }
}
