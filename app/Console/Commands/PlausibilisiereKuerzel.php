<?php

namespace App\Console\Commands;

use App\Models\ExtraktKuerzel;
use App\Models\KennzeichenKuerzel;
use Illuminate\Console\Command;

/**
 * Plausibilisiert die Bedeutung aller aktuellen Kennzeichen-Kürzel quellenübergreifend:
 *  - Quelle A: Wettbewerber-Extrakt (kennzeichenking.de) – „Kreis, Bundesland"
 *  - Quelle B: Wikipedia „Liste der Kfz-Kennzeichen in Deutschland" (amtlich, autoritativ)
 *
 * Wikipedia gilt als führende Quelle. Wo das im Produkt hinterlegte Bundesland der
 * amtlichen Zuordnung widerspricht (z. B. BO „Börde, Sachsen-Anhalt" statt „Bochum, NRW"),
 * wird korrigiert. Stimmen beide Quellen überein, ist die Angabe bestätigt. Codes ohne
 * Wikipedia-Eintrag (auslaufend/Sonderkennzeichen) werden zur Sichtung ausgewiesen.
 */
class PlausibilisiereKuerzel extends Command
{
    protected $signature = 'kuerzel:plausibilisieren {--dry : Nur zeigen, was passieren würde}';

    protected $description = 'Plausibilisiert die Bedeutung aller Kürzel gegen Wikipedia (amtlich) + Wettbewerber.';

    private const LAENDER = [
        'Baden-Württemberg', 'Bayern', 'Berlin', 'Brandenburg', 'Bremen', 'Hamburg',
        'Hessen', 'Mecklenburg-Vorpommern', 'Niedersachsen', 'Nordrhein-Westfalen',
        'Rheinland-Pfalz', 'Saarland', 'Sachsen-Anhalt', 'Sachsen', 'Schleswig-Holstein',
        'Thüringen',
    ];

    /** Verwaltungs-/Sonderzeilen aus der Wikipedia-Kreis-Spalte herausfiltern. */
    private const NOISE = ['Diplomat', 'Senat', 'Abgeordnetenhaus', 'Bürgerschaft', 'Bundes', 'Konsular'];

    public function handle(): int
    {
        $dry = $this->option('dry');
        $path = database_path('data/kfz_wikipedia.json');
        if (! is_file($path)) {
            $this->error("Wikipedia-Datenquelle fehlt: $path");
            return self::FAILURE;
        }
        $wiki = json_decode(file_get_contents($path), true);

        // Quelle A: kennzeichenking-Bedeutung je Code (zweite Quelle für den Konsens).
        $kk = [];
        foreach (ExtraktKuerzel::query()
            ->join('wettbewerber', 'wettbewerber.id', '=', 'extrakt_kuerzel.wettbewerber_id')
            ->where('wettbewerber.domain', 'kennzeichenking.de')
            ->whereNotNull('extrakt_kuerzel.bedeutung')
            ->get(['extrakt_kuerzel.code', 'extrakt_kuerzel.bedeutung']) as $r) {
            $kk[$r->code] = trim($r->bedeutung);
        }

        $bestaetigt = 0; $gesetzt = 0; $histCleanup = 0; $altUebersprungen = 0;
        $ohneQuelle = []; $konflikte = []; $quellenDiff = [];

        foreach (KennzeichenKuerzel::orderBy('code')->get() as $k) {
            // Reststände bereinigen: historische Stadt nur bei echten Altkennzeichen.
            if (! $k->ist_altkennzeichen && $k->historische_stadt) {
                if (! $dry) $k->historische_stadt = null;
                $histCleanup++;
            }

            // Altkennzeichen behalten ihren konsolidierten Wert (inkl. historischer Stadt).
            if ($k->ist_altkennzeichen) {
                $altUebersprungen++;
                if (! $dry && $k->isDirty()) $k->save();
                continue;
            }

            $w = $wiki[$k->code] ?? null;
            $wLand = $w['land'][0] ?? null;
            $kkVal = $this->clean($kk[$k->code] ?? null);
            $pLand = $this->landOf($k->bedeutung);

            // Konsens-Report: stimmt kennzeichenking beim Bundesland mit Wikipedia überein?
            if ($kkVal && $wLand && ($kl = $this->landOf($kkVal)) && $kl !== $wLand) {
                $quellenDiff[$k->code] = "kennzeichenking: $kl · Wikipedia: $wLand";
            }

            // Kanonischer, validierter Wert: kennzeichenking, sofern dessen Bundesland
            // amtlich (Wikipedia) bestätigt ist; sonst amtlicher Wikipedia-Wert.
            $canonical = null;
            if ($kkVal && (! $wLand || $this->landOf($kkVal) === $wLand)) {
                $canonical = $kkVal;
            } elseif ($w) {
                $canonical = $this->authoritativeBedeutung($w);
            }

            if (! $canonical) {
                $ohneQuelle[] = $k->code;
                if (! $dry && $k->isDirty()) $k->save();
                continue;
            }

            if ($k->bedeutung === $canonical) {
                $bestaetigt++;
            } else {
                if ($pLand && $wLand && $pLand !== $wLand) {
                    $konflikte[$k->code] = 'war "'.$k->bedeutung.'" -> "'.$canonical.'"';
                }
                $gesetzt++;
                if (! $dry) {
                    $k->bedeutung = $canonical;
                    $k->bedeutung_quelle = 'Plausibilisiert: kennzeichenking, Bundesland amtlich bestätigt (Wikipedia)';
                }
            }

            if (! $dry && $k->isDirty()) $k->save();
        }

        $this->info(($dry ? '[DRY] ' : '')."Bestätigt: $bestaetigt · gesetzt/korrigiert: $gesetzt · Altkennzeichen unberührt: $altUebersprungen · hist. Stadt bereinigt: $histCleanup");

        if ($konflikte) {
            $this->newLine();
            $this->warn('Echte Bundesland-Konflikte (amtlich korrigiert):');
            foreach ($konflikte as $c => $msg) $this->line("  $c: $msg");
        }
        if ($quellenDiff) {
            $this->newLine();
            $this->warn('Quellen-Divergenz kennzeichenking ↔ Wikipedia (Wikipedia bevorzugt):');
            foreach ($quellenDiff as $c => $msg) $this->line("  $c: $msg");
        }
        if ($ohneQuelle) {
            $this->newLine();
            $this->comment('Ohne validierbare Quelle ('.count($ohneQuelle).', Sichtung empfohlen): '.implode(', ', $ohneQuelle));
        }
        return self::SUCCESS;
    }

    /** Verwaltungs-Zusätze aus einem „Kreis, Bundesland"-Wert entfernen. */
    private function clean(?string $s): ?string
    {
        if (! $s) return null;
        $s = preg_replace('/,?\s*Senat und (Bürgerschaft|Abgeordnetenhaus)/u', '', $s);
        return trim(preg_replace('/\s+/', ' ', $s), " ,\t\n");
    }

    /** Amtliche Bedeutung „Kreis(e), Bundesland" aus dem Wikipedia-Datensatz. */
    private function authoritativeBedeutung(array $w): ?string
    {
        $land = $w['land'][0] ?? null;
        $kreise = [];
        foreach ($w['kreise'] ?? [] as $kr) {
            foreach (self::NOISE as $n) {
                if (str_contains($kr, $n)) { $kr = null; break; }
            }
            if ($kr) $kreise[] = trim($kr);
        }
        $kreise = array_values(array_unique($kreise));
        if (! $land && ! $kreise) return null;
        if (! $kreise) return $land;
        return implode(' / ', $kreise).($land ? ", $land" : '');
    }

    private function landOf(?string $s): ?string
    {
        if (! $s) return null;
        foreach (self::LAENDER as $l) {
            if (str_contains($s, $l)) return $l;
        }
        return null;
    }
}
