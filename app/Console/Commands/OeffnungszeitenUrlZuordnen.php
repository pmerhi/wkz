<?php

namespace App\Console\Commands;

use App\Models\Zulassungsstelle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Weist jeder Zulassungsstelle die offizielle Öffnungszeiten-Quell-URL zu –
 * aus bereits vorhandenen Daten (eigene website + Wettbewerber-Extrakte).
 * Bevorzugt tiefe, themenrelevante Links (…/kfz-zulassung/, …/oeffnungszeiten/).
 */
class OeffnungszeitenUrlZuordnen extends Command
{
    protected $signature = 'oz:url-zuordnen {--fresh : auch bereits gesetzte URLs neu bestimmen} {--dry : nur anzeigen}';
    protected $description = 'Setzt oeffnungszeiten_url je Stelle aus bestehenden Quellen (beste Trefferwahl).';

    private const KEYWORDS = ['oeffnungszeiten', 'öffnungszeiten', 'kfz-zulassung', 'zulassungsstelle', 'zulassung', 'kfz'];

    /** Kommerzielle Portale/Wettbewerber – keine offiziellen Behördenquellen. */
    private const WETTBEWERBER = ['kennzeichenking.de', 'zulassungsstelle.de', 'gutschild.de',
        'strassenverkehrsamt.de', 'kfzkennzeichen.online', 'kfz-kennzeichen.net', 'wunschkennzeichen',
        'kroschke.de', 'zulassungsdienst', 'schilderportal', 'kennzeichen24', 'zulassung-ulm.de'];

    public function handle(): int
    {
        $dry = $this->option('dry');
        $fresh = $this->option('fresh');

        $gesetzt = 0; $luecke = 0; $uebersprungen = 0;
        $query = Zulassungsstelle::whereNull('parent_id');

        foreach ($query->cursor() as $s) {
            if ($s->oeffnungszeiten_url && ! $fresh) { $uebersprungen++; continue; }
            // Verifizierte Recherche-URLs nie automatisch überschreiben.
            if ($s->oeffnungszeiten_url_quelle === 'manuell-recherche') { $uebersprungen++; continue; }

            $kandidaten = [];
            if ($s->website) {
                $kandidaten[] = ['url' => $s->website, 'quelle' => 'website', 'eigen' => true];
            }
            // Wettbewerber-Extrakte zur selben Gemeinde, sonst Kreis.
            foreach ([['gemeinde_id', $s->gemeinde_id], ['kreis_id', $s->kreis_id]] as [$sp, $val]) {
                if (! $val) continue;
                $ex = DB::table('extrakt_zulassungsstelle')
                    ->join('wettbewerber', 'wettbewerber.id', '=', 'extrakt_zulassungsstelle.wettbewerber_id')
                    ->where('extrakt_zulassungsstelle.'.$sp, $val)
                    ->get(['wettbewerber.domain', 'website', 'quelle_url']);
                foreach ($ex as $r) {
                    if ($r->website)    $kandidaten[] = ['url' => $r->website, 'quelle' => $r->domain, 'eigen' => false];
                    if ($r->quelle_url) $kandidaten[] = ['url' => $r->quelle_url, 'quelle' => $r->domain, 'eigen' => false];
                }
                if ($ex->isNotEmpty()) break; // Gemeinde-Treffer reichen, sonst Kreis
            }

            $best = $this->beste($kandidaten);
            if (! $best) { $luecke++; continue; }

            if ($dry) {
                $this->line(sprintf('  %-45s → [%s] %s', mb_substr($s->name, 0, 45), $best['quelle'], $best['url']));
            } else {
                $s->oeffnungszeiten_url = $best['url'];
                $s->oeffnungszeiten_url_quelle = $best['quelle'];
                $s->save();
            }
            $gesetzt++;
        }

        $this->info(($dry ? '[DRY] ' : '')."URL gesetzt: $gesetzt · Lücke (keine Quelle): $luecke · übersprungen (schon gesetzt): $uebersprungen");
        return self::SUCCESS;
    }

    /** Höchstbewerteten Kandidaten wählen (Themen-Tiefe > eigene Domain). */
    private function beste(array $kandidaten): ?array
    {
        $best = null; $bestScore = -99;
        foreach ($kandidaten as $k) {
            $url = $k['url'];
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            $path = strtolower((string) parse_url($url, PHP_URL_PATH));
            $istWettbewerber = false;
            foreach (self::WETTBEWERBER as $w) {
                if (str_contains($host, $w)) { $istWettbewerber = true; break; }
            }
            $score = 0;
            // Offizielle Quelle stark bevorzugen, Wettbewerber nur als Notnagel.
            $score += $istWettbewerber ? -10 : 5;
            foreach (self::KEYWORDS as $kw) {
                if (str_contains(strtolower($url), $kw)) { $score += 3; break; }
            }
            if ($path !== '' && $path !== '/') $score += 2;      // tiefer Link
            if (str_starts_with($url, 'https')) $score += 1;
            if ($k['eigen']) $score += 1;                         // eigene website leicht bevorzugt
            if ($score > $bestScore) { $bestScore = $score; $best = $k; }
        }

        return $best;
    }
}
