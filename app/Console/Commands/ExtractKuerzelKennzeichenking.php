<?php

namespace App\Console\Commands;

use App\Models\ExtraktKuerzel;
use App\Models\KennzeichenKuerzel;
use App\Models\Wettbewerber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Extrahiert die Kennzeichen-Kürzel-Liste von kennzeichenking.de
 * (Code · Ursprung · Stadt/Landkreis · Bundesland) → extrakt_kuerzel.
 * AGS-Bezug über die bestehende Kürzel↔Kreis-Verknüpfung. INTERN.
 */
class ExtractKuerzelKennzeichenking extends Command
{
    protected $signature = 'extract:kuerzel-kennzeichenking';

    protected $description = 'Extrahiert Kennzeichen-Kürzel (Bedeutung/Kreis) von kennzeichenking.de.';

    public function handle(): int
    {
        $w = Wettbewerber::where('domain', 'kennzeichenking.de')->first();
        if (! $w) { $this->error('Wettbewerber fehlt.'); return self::FAILURE; }

        $url = 'https://www.kennzeichenking.de/kfz-kennzeichen-liste';
        try { $html = Http::timeout(40)->withHeaders(['User-Agent' => 'WunschkennzeichenPortal-Research/1.0'])->get($url)->body(); }
        catch (\Throwable) { $this->error('Abruf fehlgeschlagen.'); return self::FAILURE; }

        // Code → Kreis-ID aus unserer bestehenden Kürzel↔Kreis-Verknüpfung
        $kreisByCode = [];
        foreach (KennzeichenKuerzel::with('kreise:id')->get() as $k) {
            $kreisByCode[$k->code] = optional($k->kreise->first())->id;
        }

        // Tabellenzeilen parsen
        $pattern = '~<td data-label="Ortskürzel">\s*<a[^>]*>([^<]+)</a>\s*</td>'
            .'\s*<td data-label="Ursprung">([^<]*)</td>'
            .'\s*<td data-label="Stadt / Landkreis">([^<]*)</td>'
            .'\s*<td data-label="Bundesland">([^<]*)</td>~i';
        preg_match_all($pattern, $html, $rows, PREG_SET_ORDER);
        $this->info('Zeilen gefunden: '.count($rows));

        ExtraktKuerzel::where('wettbewerber_id', $w->id)->delete();
        $n = 0;
        foreach ($rows as $r) {
            $code = trim(html_entity_decode($r[1]));
            $kreis = trim(html_entity_decode($r[3]));
            $land = trim(html_entity_decode($r[4]));
            if ($code === '') continue;
            $bedeutung = $kreis !== '' ? ($land !== '' ? "$kreis, $land" : $kreis) : null;
            ExtraktKuerzel::create([
                'wettbewerber_id' => $w->id,
                'code'            => mb_substr($code, 0, 3),
                'bedeutung'       => $bedeutung ? mb_substr($bedeutung, 0, 255) : null,
                'kreis_id'        => $kreisByCode[$code] ?? null,
                'quelle_url'      => $url,
            ]);
            $n++;
        }

        $this->info("Fertig. Kürzel extrahiert: $n · mit AGS-Kreis: ".ExtraktKuerzel::where('wettbewerber_id', $w->id)->whereNotNull('kreis_id')->count());
        $this->comment('INTERN. Veröffentlichung erst nach anwaltlicher Freigabe.');
        return self::SUCCESS;
    }
}
