<?php

namespace App\Console\Commands;

use App\Models\Zulassungsstelle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Trägt fehlende Geokoordinaten (lat/lng) der Zulassungsstellen per Nominatim
 * (OpenStreetMap) nach – damit die Standortkarte erscheint. Nominatim erlaubt
 * max. 1 Anfrage/Sekunde und verlangt einen aussagekräftigen User-Agent.
 *
 * Robust: säubert die Straße (Klammer-Zusätze, „Hauptstelle:", „Ecke …",
 * HTML-Zeichen) und fällt bei Nichttreffer auf die Ortsmitte (PLZ+Ort) zurück.
 * Datensätze mit Telefon/E-Mail statt Adresse werden gemeldet, nicht geokodiert.
 */
class StellenGeocode extends Command
{
    protected $signature = 'stellen:geocode
        {--limit=0 : Nur die ersten N Stellen (0 = alle)}
        {--dry-run : Nur anzeigen, nichts speichern}';

    protected $description = 'Geokodiert Zulassungsstellen ohne lat/lng über Nominatim (OSM).';

    public function handle(): int
    {
        $q = Zulassungsstelle::where(fn ($x) => $x->whereNull('lat')->orWhereNull('lng'))
            ->whereNotNull('strasse')->where('strasse', '!=', '')
            ->where(fn ($x) => $x->whereNotNull('plz')->orWhereNotNull('ort'))
            ->orderBy('id');
        if ($lim = (int) $this->option('limit')) {
            $q->limit($lim);
        }
        $stellen = $q->get();
        $this->info($stellen->count().' Stellen zu geokodieren …');

        $ok = 0;
        $grob = 0;
        $junk = [];
        $fail = [];
        foreach ($stellen as $s) {
            if ($grund = $this->datenfehler($s)) {
                $junk[] = "#{$s->id}: {$grund}";
                continue;
            }
            [$lat, $lng, $genau] = $this->geocode($s);
            if ($lat && $lng) {
                if (! $this->option('dry-run')) {
                    $s->forceFill(['lat' => $lat, 'lng' => $lng])->saveQuietly();
                }
                $ok++;
                $genau ? null : $grob++;
                $this->line(sprintf('  %s #%-5d %-28s %s,%s', $genau ? '✓' : '≈', $s->id, mb_substr($s->strasse.' '.$s->ort, 0, 28), $lat, $lng));
            } else {
                $fail[] = "#{$s->id} {$s->strasse}, {$s->plz} {$s->ort}";
            }
        }

        $this->newLine();
        $this->info("Fertig. {$ok} gesetzt (davon {$grob} nur Ortsmitte), ".count($fail)." ohne Treffer, ".count($junk)." Datenfehler.".($this->option('dry-run') ? ' (dry-run)' : ''));
        if ($junk) {
            $this->warn('Datenfehler (nicht geokodiert – bitte korrigieren):');
            foreach ($junk as $j) $this->line('  ⚠ '.$j);
        }
        if ($fail) {
            $this->warn('Ohne Treffer:');
            foreach ($fail as $f) $this->line('  ✗ '.$f);
        }
        return self::SUCCESS;
    }

    /** Prüft, ob Adressfelder offensichtlich falsch belegt sind (Tel/E-Mail/Postfach). */
    private function datenfehler(Zulassungsstelle $s): ?string
    {
        if (str_contains((string) $s->ort, '@'))                                return 'E-Mail im Ort-Feld ('.$s->ort.')';
        if (preg_match('/\bPostfach\b/i', (string) $s->strasse))                return 'Postfach statt Straße';
        if (! preg_match('/\p{L}{3,}/u', (string) $s->strasse))                 return 'keine Straße (Tel.?) in strasse ('.$s->strasse.')';
        if (preg_match('/\d{2,}[\s\/-]\d{2,}/', (string) $s->plz))              return 'Telefonnummer im PLZ-Feld ('.$s->plz.')';
        return null;
    }

    /** Bereinigt die Straße von Zusätzen/Präfixen/Tippfehler-Störern. */
    private function bereinigeStrasse(string $strasse): string
    {
        $s = html_entity_decode($strasse, ENT_QUOTES | ENT_HTML5);
        // Label vor Doppelpunkt weg ("Hauptstelle: Adenauerring 1" → "Adenauerring 1")
        if (str_contains($s, ':')) {
            $s = substr($s, strpos($s, ':') + 1);
        }
        $s = preg_replace('/\([^)]*\)/', '', $s);          // Klammer-Zusätze
        $s = preg_split('#\s*(?:Ecke|/)\s*#i', $s)[0];     // "Ecke …" / "…/…" → erster Teil
        return trim(preg_replace('/\s+/', ' ', $s));
    }

    /** Geocode mit Bereinigung + Ortsmitte-Fallback. Gibt [lat, lng, genau] zurück. */
    private function geocode(Zulassungsstelle $s): array
    {
        $street = $this->bereinigeStrasse((string) $s->strasse);

        // 1) strukturiert (bereinigte Straße)  2) Freitext
        $hit = $this->nominatim(array_filter(['street' => $street ?: null, 'postalcode' => $s->plz ?: null, 'city' => $s->ort ?: null]));
        if (! $hit && $street) {
            $hit = $this->nominatim(['q' => trim($street.', '.$s->plz.' '.$s->ort, ' ,')]);
        }
        if ($hit) {
            return [round((float) $hit['lat'], 6), round((float) $hit['lon'], 6), true];
        }

        // 3) Ortsmitte (PLZ + Ort) – grobe Koordinate ist besser als keine Karte
        if ($s->plz || $s->ort) {
            $hit = $this->nominatim(['q' => trim(($s->plz ?: '').' '.($s->ort ?: ''))]);
            if ($hit) {
                return [round((float) $hit['lat'], 6), round((float) $hit['lon'], 6), false];
            }
        }

        return [null, null, false];
    }

    /** Eine Nominatim-Anfrage inkl. verpflichtender Rate-Bremse (1/Sek.). */
    private function nominatim(array $params): ?array
    {
        usleep(1_100_000); // max. 1 Anfrage/Sekunde (Nominatim-Policy)
        $resp = Http::timeout(20)->retry(2, 1500, throw: false)
            ->withHeaders(['User-Agent' => 'WKR-Portal/1.0 (+'.config('app.url').'; kontakt: patrick@merhi.de)'])
            ->get('https://nominatim.openstreetmap.org/search', array_merge(
                ['format' => 'json', 'countrycodes' => 'de', 'limit' => 1, 'addressdetails' => 0],
                $params,
            ));

        return $resp->ok() ? ($resp->json()[0] ?? null) : null;
    }
}
