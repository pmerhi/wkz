<?php

namespace App\Console\Commands;

use App\Models\Zulassungsstelle;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Ruft die hinterlegte Öffnungszeiten-Quell-URL je Stelle ab, bildet einen
 * Fingerprint des zeit-relevanten Inhalts (Wochentage + Uhrzeitspannen) und
 * markiert Stellen, deren Seite sich seit der letzten Prüfung geändert hat.
 */
class OeffnungszeitenPruefen extends Command
{
    protected $signature = 'oz:pruefen
        {--stale=7 : nur Stellen prüfen, die seit N Tagen nicht geprüft wurden (0 = alle)}
        {--limit=0 : maximal so viele prüfen (0 = unbegrenzt)}
        {--id= : nur diese Stellen-ID prüfen}';

    protected $description = 'Prüft die Öffnungszeiten-Quellseiten auf Änderungen (Fingerprint-Vergleich).';

    public function handle(): int
    {
        $query = Zulassungsstelle::whereNull('parent_id')->whereNotNull('oeffnungszeiten_url');

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        } elseif (($stale = (int) $this->option('stale')) > 0) {
            $grenze = Carbon::now()->subDays($stale);
            $query->where(fn ($q) => $q->whereNull('oeffnungszeiten_geprueft_at')
                ->orWhere('oeffnungszeiten_geprueft_at', '<', $grenze));
        }
        if (($limit = (int) $this->option('limit')) > 0) {
            $query->limit($limit);
        }

        $geprueft = 0; $geaendert = 0; $erstmalig = 0; $ohneZeiten = 0; $fehler = 0;

        foreach ($query->cursor() as $s) {
            $html = $this->lade($s->oeffnungszeiten_url);
            if ($html === null) {
                $fehler++;
                $this->warn("  ✗ {$s->name}: nicht erreichbar ({$s->oeffnungszeiten_url})");
                continue;
            }

            $fingerprint = $this->fingerprint($html);
            $geprueft++;

            if ($fingerprint === null) {
                $ohneZeiten++;
                $s->oeffnungszeiten_geprueft_at = now();
                $s->save();
                $this->line("  ? {$s->name}: keine Zeiten auf der Seite erkannt (evtl. JS/falsche URL)");
                continue;
            }

            $hash = sha1($fingerprint);

            if (! $s->oeffnungszeiten_hash) {
                $erstmalig++;
                $s->oeffnungszeiten_hash = $hash;
            } elseif ($s->oeffnungszeiten_hash !== $hash) {
                $geaendert++;
                $s->oeffnungszeiten_hash = $hash;
                $s->oeffnungszeiten_geaendert = true;
                $this->info("  ⚑ ÄNDERUNG: {$s->name} → {$s->oeffnungszeiten_url}");
            }
            $s->oeffnungszeiten_geprueft_at = now();
            $s->save();
        }

        $this->info("Geprüft: $geprueft · Änderungen: $geaendert · erstmalig erfasst: $erstmalig · ohne erkennbare Zeiten: $ohneZeiten · Fehler: $fehler");
        return self::SUCCESS;
    }

    private function lade(string $url): ?string
    {
        try {
            $res = Http::withHeaders([
                'User-Agent' => 'WunschkennzeichenPortal-OZMonitor/1.0 (+https://wunschkennzeichen-portal.de)',
                'Accept'     => 'text/html',
            ])->timeout(15)->retry(1, 500)->get($url);

            return $res->successful() ? $res->body() : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Zeit-relevanten Fingerprint bilden: Wochentage + Uhrzeitspannen aus dem
     * sichtbaren Text. Unabhängig von unwichtigen Seitenänderungen (Ads, Datum).
     */
    private function fingerprint(string $html): ?string
    {
        // Skripte/Styles entfernen, Tags strippen, Entities + Whitespace normalisieren.
        $text = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = mb_strtolower(preg_replace('/\s+/u', ' ', $text));

        // Tag + Uhrzeitspanne (z.B. "montag 08:00 - 12:30", "mo 8:00–14 uhr").
        $tage = 'montag|dienstag|mittwoch|donnerstag|freitag|samstag|sonntag|mo|di|mi|do|fr|sa|so';
        preg_match_all(
            '/(?:'.$tage.')\b[^.\n]{0,30}?\d{1,2}[:.]\d{2}\s*(?:-|–|bis)\s*\d{1,2}[:.]\d{2}/u',
            $text, $m1
        );
        $segmente = $m1[0];

        // Fallback: alle Uhrzeitspannen, falls keine Tag-Zuordnung erkannt wurde.
        if (! $segmente) {
            preg_match_all('/\d{1,2}[:.]\d{2}\s*(?:-|–|bis)\s*\d{1,2}[:.]\d{2}/u', $text, $m2);
            $segmente = $m2[0];
        }
        if (! $segmente) {
            return null;
        }

        $segmente = array_map(fn ($x) => preg_replace('/\s+/', '', $x), $segmente);
        $segmente = array_values(array_unique($segmente));
        sort($segmente);

        return implode('|', $segmente);
    }
}
