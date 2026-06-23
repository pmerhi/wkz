<?php

namespace App\Console\Commands;

use App\Models\KennzeichenKuerzel;
use App\Models\Kreis;
use Illuminate\Console\Command;

/**
 * Klärt die 17 Grenzfälle der Plausibilisierung abschließend:
 *
 *  (A) 11 wieder eingeführte Sekundär-Altkennzeichen, die im Artikel
 *      „Kennzeichenliberalisierung" (Gesamtübersicht, mit AGS) als reaktiviert
 *      geführt sind, aber in der A–Z-Hauptliste nur als auslaufend (fett) stehen.
 *      → als Altkennzeichen markieren, heutigen Kreis + AGS + Bundesland setzen.
 *
 *  (B) 6 Artefakte aus dem ursprünglichen Wikidata-Import (P395), die keine
 *      gültigen deutschen Unterscheidungszeichen sind (z. B. AG „Helgoland",
 *      FSU/OPO/DBA = polnische Orte). → entfernen, sofern ohne Stelle/Quelle.
 */
class ImportAltkennzeichenSekundaer extends Command
{
    protected $signature = 'import:altkennzeichen-sekundaer {--dry : Nur zeigen, was passieren würde}';

    protected $description = 'Markiert 11 Sekundär-Altkennzeichen (Gesamtübersicht) und entfernt 6 Wikidata-Artefakte.';

    /** code => [historische Stadt, heutiger Kreis, AGS (5-stellig), Bundesland]. */
    private const SEKUNDAER = [
        'ASD' => ['Aschendorf',           'Landkreis Emsland',        '03454', 'Niedersachsen'],
        'LIN' => ['Lingen',               'Landkreis Emsland',        '03454', 'Niedersachsen'],
        'MEP' => ['Meppen',               'Landkreis Emsland',        '03454', 'Niedersachsen'],
        'BEI' => ['Beilngries',           'Landkreis Eichstätt',      '09176', 'Bayern'],
        'GEM' => ['Gemünden am Main',     'Landkreis Main-Spessart',  '09677', 'Bayern'],
        'MAR' => ['Marktheidenfeld',      'Landkreis Main-Spessart',  '09677', 'Bayern'],
        'MT'  => ['Montabaur',            'Westerwaldkreis',          '07143', 'Rheinland-Pfalz'],
        'SFA' => ['Soltau-Fallingbostel', 'Heidekreis',               '03358', 'Niedersachsen'],
        'SOL' => ['Soltau',               'Heidekreis',               '03358', 'Niedersachsen'],
        'WEG' => ['Wegscheid',            'Landkreis Passau',         '09275', 'Bayern'],
        'WEM' => ['Wesermünde',           'Landkreis Cuxhaven',       '03352', 'Niedersachsen'],
    ];

    /** Wikidata-Artefakte: keine gültigen deutschen Kfz-Unterscheidungszeichen. */
    private const ARTEFAKTE = ['AG', 'DBA', 'EO', 'ET', 'FSU', 'OPO'];

    public function handle(): int
    {
        $dry = $this->option('dry');
        $kreisByAgs = Kreis::whereIn('ags', array_column(self::SEKUNDAER, 2))->get()->keyBy('ags');

        $markiert = 0; $verknuepft = 0;
        foreach (self::SEKUNDAER as $code => [$stadt, $kreisName, $ags, $land]) {
            $k = KennzeichenKuerzel::where('code', $code)->first();
            if (! $k) { $this->warn("  $code: Zeile fehlt, übersprungen"); continue; }

            $kreis = $kreisByAgs[$ags] ?? null;
            $this->line("  $code: $stadt → $kreisName, $land".($kreis ? " (Kreis-ID {$kreis->id})" : ' (kein Kreis-Link)'));
            $markiert++;
            if (! $dry) {
                $k->update([
                    'ist_altkennzeichen' => true,
                    'historische_stadt'  => $stadt,
                    'bedeutung'          => "$kreisName, $land",
                    'bedeutung_quelle'   => 'Wikipedia Kennzeichenliberalisierung (Gesamtübersicht, mit AGS)',
                ]);
                if ($kreis) { $k->kreise()->syncWithoutDetaching([$kreis->id]); $verknuepft++; }
            } elseif ($kreis) {
                $verknuepft++;
            }
        }

        // (B) Artefakte entfernen – nur wenn ohne Stelle und ohne Wettbewerber-Quelle.
        $entfernt = 0; $behalten = [];
        foreach (self::ARTEFAKTE as $code) {
            $k = KennzeichenKuerzel::where('code', $code)->first();
            if (! $k) continue;
            $hatStellen = $k->zulassungsstellen()->count() > 0;
            if ($hatStellen) { $behalten[] = "$code (hat Stellen)"; continue; }
            $this->line("  entferne $code: \"{$k->bedeutung}\" (Wikidata-Artefakt)");
            $entfernt++;
            if (! $dry) $k->delete();
        }

        $this->newLine();
        $this->info(($dry ? '[DRY] ' : '')."Sekundär-Altkennzeichen markiert: $markiert · Kreis verknüpft: $verknuepft · Artefakte entfernt: $entfernt");
        if ($behalten) $this->warn('Nicht entfernt (manuelle Prüfung): '.implode(', ', $behalten));
        $this->comment('Quelle: Wikipedia „Kennzeichenliberalisierung" (Gesamtübersicht). AGS-Bezug über Kreis-Verknüpfung.');
        return self::SUCCESS;
    }
}
