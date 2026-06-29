<?php

namespace App\Console\Commands;

use App\Models\Zulassungsstelle;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Versucht für Stellen mit toter Öffnungszeiten-URL eine LEBENDE Alternative aus
 * den vorhandenen Daten (eigene website + Wettbewerber-Extrakte) zu finden und
 * live zu prüfen – bevor teure Recherche nötig wird.
 */
class OzUrlRescue extends Command
{
    protected $signature = 'oz:url-rescue {--limit=0} {--dry}';
    protected $description = 'Ersetzt tote Öffnungszeiten-URLs durch lebende Alternativen aus eigenen Daten.';

    private const UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';
    private const WETTBEWERBER = ['kennzeichenking.de', 'zulassungsstelle.de', 'gutschild.de', 'strassenverkehrsamt.de',
        'kfzkennzeichen.online', 'kfz-kennzeichen.net', 'wunschkennzeichen', 'kroschke.de', 'zulassungsdienst', 'zulassung-ulm.de'];

    public function handle(): int
    {
        $dry = $this->option('dry');
        $query = Zulassungsstelle::whereNull('parent_id')->whereNotNull('oeffnungszeiten_url')
            ->where(fn ($q) => $q->whereIn('oeffnungszeiten_url_status', [0, 404, 410])
                ->orWhere('oeffnungszeiten_url_status', '>=', 500));
        if (($limit = (int) $this->option('limit')) > 0) {
            $query->limit($limit);
        }
        $stellen = $query->get();

        $gerettet = 0; $weiterhinTot = 0;
        foreach ($stellen as $s) {
            $kandidaten = $this->kandidaten($s);
            $treffer = null;
            foreach (array_chunk($kandidaten, 10) as $gruppe) {
                $resp = Http::pool(fn ($pool) => collect($gruppe)->mapWithKeys(fn ($u, $i) => [
                    (string) $i => $pool->as((string) $i)->withHeaders(['User-Agent' => self::UA])
                        ->withOptions(['verify' => false, 'allow_redirects' => true])->timeout(15)->get($u),
                ])->all());
                foreach ($gruppe as $i => $u) {
                    $r = $resp[(string) $i] ?? null;
                    $code = $r instanceof Response ? $r->status() : 0;
                    if (! $this->istTot($code)) { $treffer = [$u, $code]; break 2; }
                }
            }

            if ($treffer) {
                $gerettet++;
                if (! $dry) {
                    $s->oeffnungszeiten_url = $treffer[0];
                    $s->oeffnungszeiten_url_quelle = 'auto-alternative';
                    $s->oeffnungszeiten_url_status = $treffer[1];
                    $s->saveQuietly();
                }
                $this->line("  ✓ {$s->name}: ".$treffer[0]);
            } else {
                $weiterhinTot++;
            }
        }

        $this->info(($dry ? '[DRY] ' : '')."Gerettet: $gerettet · weiterhin tot (→ Recherche): $weiterhinTot von ".$stellen->count());
        return self::SUCCESS;
    }

    /** Alternative URLs aus eigenen Daten (ohne die aktuelle tote, ohne Wettbewerber). */
    private function kandidaten(Zulassungsstelle $s): array
    {
        $aktuell = html_entity_decode((string) $s->oeffnungszeiten_url);
        $urls = [];
        if ($s->website) {
            $urls[] = $s->website;
        }
        foreach ([['gemeinde_id', $s->gemeinde_id], ['kreis_id', $s->kreis_id]] as [$sp, $val]) {
            if (! $val) {
                continue;
            }
            $ex = DB::table('extrakt_zulassungsstelle')
                ->join('wettbewerber', 'wettbewerber.id', '=', 'extrakt_zulassungsstelle.wettbewerber_id')
                ->where('extrakt_zulassungsstelle.'.$sp, $val)
                ->get(['website', 'quelle_url']);
            foreach ($ex as $r) {
                if ($r->website) {
                    $urls[] = $r->website;
                }
                if ($r->quelle_url) {
                    $urls[] = $r->quelle_url;
                }
            }
            if ($ex->isNotEmpty()) {
                break;
            }
        }

        // Normalisieren, Duplikate/aktuelle/Wettbewerber raus.
        $sauber = [];
        foreach ($urls as $u) {
            $u = html_entity_decode($u);
            if ($u === $aktuell) {
                continue;
            }
            $host = strtolower((string) parse_url($u, PHP_URL_HOST));
            foreach (self::WETTBEWERBER as $w) {
                if (str_contains($host, $w)) {
                    continue 2;
                }
            }
            $sauber[$u] = true;
        }

        return array_keys($sauber);
    }

    private function istTot(int $status): bool
    {
        return $status === 0 || $status === 404 || $status === 410 || $status >= 500;
    }
}
