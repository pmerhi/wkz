<?php

namespace App\Console\Commands;

use App\Models\AltkennzeichenStatus;
use Illuminate\Console\Command;

class GenerateInfografikDaten extends Command
{
    protected $signature = 'infografik:daten {--path= : Zielpfad der JS-Datei}';

    protected $description = 'Erzeugt die Daten der Altkennzeichen-Infografik (public/infografik/daten.js) aus der eigenen DB';

    public function handle(): int
    {
        // Quelle ist die in unsere DB übernommene Status-Tabelle (inkl. abgelehnter/beantragter Kennzeichen).
        $rows = AltkennzeichenStatus::orderBy('kgs')->orderBy('kuerzel')->get();

        if ($rows->isEmpty()) {
            $this->warn('Tabelle altkennzeichen_status ist leer. Zuerst `php artisan altkennzeichen:status-import` ausführen.');
        }

        // Format wie vom infomap.js erwartet, gekeyt nach KGS.
        // Status-Codes 0-12 entsprechen der Legende (grün = eingeführt, gelb = beantragt/unsicher, rot = abgelehnt).
        $data = [];
        foreach ($rows as $r) {
            $kgs = (string) $r->kgs;
            if (! isset($data[$kgs])) {
                $data[$kgs] = [
                    'kennzeichen' => [],
                    'bundesland'  => $r->bundesland ?? '',
                    'kreisname'   => $r->kreisname ?? '',
                    'kgs'         => $kgs,
                ];
            }
            $eintrag = [
                'status'  => (string) $r->status,
                'kuerzel' => $r->kuerzel,
            ];
            if ($r->in_klammern) {
                $eintrag['in_klammern'] = true;
            }
            $data[$kgs]['kennzeichen'][$r->kuerzel] = $eintrag;
        }

        // Leere kennzeichen-Maps zu JS-Objekten zwingen (sonst macht json_encode ein Array daraus).
        foreach ($data as &$d) {
            if (empty($d['kennzeichen'])) {
                $d['kennzeichen'] = (object) [];
            }
        }
        unset($d);

        ksort($data);

        // Status-Übersicht fürs Log.
        $statusZaehler = $rows->groupBy('status')->map->count()->sortKeys();

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $stand = now()->format('d.m.Y H:i');
        $js = "/* Automatisch erzeugt aus der Portal-DB (Tabelle altkennzeichen_status) – Stand {$stand}. Nicht manuell bearbeiten. */\n"
            ."/* Quelle: php artisan infografik:daten */\n"
            ."var INFOMAP_DATA = {$json};\n";

        $path = $this->option('path') ?: public_path('infografik/daten.js');
        file_put_contents($path, $js);

        $this->info('Infografik-Daten geschrieben: '.$path);
        $this->line('  Kreise/KGS: '.count($data).' · Kennzeichen-Einträge: '.$rows->count());
        $abgelehnt = $rows->whereIn('status', [9, 10])->count();
        $this->line('  davon abgelehnt (Status 9/10, rot): '.$abgelehnt
            .' · beantragt/unsicher (2/3, gelb): '.$rows->whereIn('status', [2, 3])->count());

        return self::SUCCESS;
    }
}
