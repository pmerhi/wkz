<?php

namespace App\Console\Commands;

use App\Models\CrawlSeite;
use App\Models\ExtraktZulassungsstelle;
use App\Models\Gemeinde;
use App\Models\Wettbewerber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Blaupause-Extraktor für zulassungsstelle.de (Avada/Fusion-Template).
 * Pro Kreis-Seite werden 1+ Zulassungsstellen geparst und an die AGS-Struktur
 * (Gemeinde → Kreis) gebunden. INTERN — keine Veröffentlichung ohne Freigabe.
 */
class ExtractZulassungsstelleDe extends Command
{
    protected $signature = 'extract:zulassungsstelle-de {--limit=10} {--delay=800}';

    protected $description = 'Extrahiert Zulassungsstellen von zulassungsstelle.de (AGS-verknüpft, intern).';

    private string $ua = 'WunschkennzeichenPortal-Research/1.0 (interne Wettbewerbsanalyse)';

    public function handle(): int
    {
        $w = Wettbewerber::where('domain', 'zulassungsstelle.de')->first();
        if (! $w) { $this->error('Wettbewerber fehlt.'); return self::FAILURE; }

        // Gemeinde-Index: normName => Liste [gemeinde_id, kreis_id, bundesland_id]
        $gIndex = [];
        foreach (Gemeinde::select('id', 'name', 'kreis_id', 'bundesland_id')->cursor() as $g) {
            $n = $this->norm($g->name);
            if ($n !== '') $gIndex[$n][] = [$g->id, $g->kreis_id, $g->bundesland_id];
        }
        $plzLandIds = $this->plzLandIds();

        // PLZ → [gemeinde_id, kreis_id] (exaktes Match, primär)
        $plzIndex = [];
        foreach (DB::table('plz_gemeinde')->select('plz', 'gemeinde_id', 'kreis_id')->get() as $r) {
            $plzIndex[$r->plz] ??= [$r->gemeinde_id, $r->kreis_id];
        }

        $urls = $this->stelleUrls();
        if (empty($urls)) { $this->error('Keine Stelle-URLs gefunden.'); return self::FAILURE; }
        $this->info('Stelle-Seiten gesamt: '.count($urls).' — verarbeite '.min((int) $this->option('limit'), count($urls)));

        $delay = (int) $this->option('delay') * 1000;
        $offices = 0; $matched = 0; $pages = 0;

        foreach (array_slice($urls, 0, (int) $this->option('limit')) as $url) {
            try {
                $res = Http::timeout(30)->withHeaders(['User-Agent' => $this->ua])->get($url);
            } catch (\Throwable $e) { $this->warn("  Fehler $url"); continue; }
            if (! $res->successful()) { usleep($delay); continue; }
            $html = $res->body();
            $pages++;

            $seite = CrawlSeite::updateOrCreate(
                ['wettbewerber_id' => $w->id, 'url_hash' => sha1($url)],
                ['url' => $url, 'http_status' => $res->status(), 'content_type' => $res->header('Content-Type'),
                 'titel' => $this->title($html), 'html' => $html, 'abgerufen_am' => now()],
            );

            foreach ($this->parseOffices($html) as $o) {
                try {
                    $hit = $this->matchGemeinde($o['ort'], $o['plz'], $gIndex, $plzLandIds, $plzIndex);
                    ExtraktZulassungsstelle::updateOrCreate(
                        ['wettbewerber_id' => $w->id, 'quelle_url' => $url, 'name' => mb_substr($o['name'], 0, 255)],
                        [
                            'crawl_seite_id' => $seite->id,
                            'strasse' => mb_substr((string) $o['strasse'], 0, 255), 'plz' => $o['plz'],
                            'ort' => mb_substr((string) $o['ort'], 0, 255),
                            'telefon' => mb_substr((string) $o['telefon'], 0, 255),
                            'email' => mb_substr((string) $o['email'], 0, 255),
                            'website' => mb_substr((string) $o['website'], 0, 255),
                            'termin_url' => mb_substr((string) $o['termin'], 0, 255),
                            'gemeinde_id' => $hit[0] ?? null, 'kreis_id' => $hit[1] ?? null,
                            'roh' => $o,
                        ],
                    );
                    $offices++;
                    if ($hit) $matched++;
                } catch (\Throwable $e) {
                    $this->warn('  Skip Stelle: '.Str::limit($e->getMessage(), 80));
                }
            }
            $this->line("  [{$res->status()}] $url");
            usleep($delay);
        }

        $this->info("Fertig. Seiten: $pages · Stellen extrahiert: $offices · davon AGS-gematcht: $matched");
        $this->comment('INTERN. Veröffentlichung erst nach anwaltlicher Freigabe (siehe recht/).');
        return self::SUCCESS;
    }

    /** URLs aller Stelle-Seiten aus der Verzeichnis-Liste (Archiv oder live). */
    private function stelleUrls(): array
    {
        $listUrl = 'https://zulassungsstelle.de/kfz-zulassungsstellen-liste-staedte-landkreise/';
        $html = optional(CrawlSeite::where('url', $listUrl)->first())->html;
        if (! $html) {
            try { $html = Http::timeout(30)->withHeaders(['User-Agent' => $this->ua])->get($listUrl)->body(); }
            catch (\Throwable) { return []; }
        }
        preg_match_all('~href="(https://zulassungsstelle\.de/[a-z0-9-]+/)"~', $html, $m);
        $skip = ['/wp-', '/feed', '/comments', 'kfz-', 'wunschkennzeichen', 'vergleich', 'versicherung',
                 '/blog/', 'ueber-uns', 'kontakt', 'impressum', 'agb', 'datenschutz', 'cookie',
                 'haftung', 'widerruf', '/author/'];
        $out = [];
        foreach (array_unique($m[1]) as $u) {
            $bad = false;
            foreach ($skip as $s) { if (str_contains($u, $s)) { $bad = true; break; } }
            if (! $bad) $out[] = $u;
        }
        return array_values($out);
    }

    /** Parst Office-Blöcke (Name + Anschrift + Tel/E-Mail/Website/Termin). */
    private function parseOffices(string $html): array
    {
        // Jeder Block: <h2>…Zulassungsstelle…</h2> <p>Straße<br>PLZ Ort</p> … bis zum nächsten <h2>
        $pattern = '~<h2[^>]*>([^<]*?Zulassungsstelle[^<]*?)</h2>\s*<p>(.*?)<br\s*/?>\s*(\d{5})\s+([^<]+?)</p>(.*?)(?=<h2|<div class="fusion-text fusion-text|$)~is';
        preg_match_all($pattern, $html, $blocks, PREG_SET_ORDER);

        $out = [];
        foreach ($blocks as $b) {
            // Anschrift: <br> → Zeilen; letzte Zeile = Straße, frühere = Behörden-Zusatz
            $addrLines = array_values(array_filter(array_map(
                'trim', explode("\n", $this->clean(preg_replace('~<br\s*/?>~i', "\n", $b[2])))
            )));
            $strasse = $addrLines ? end($addrLines) : $this->clean($b[2]);

            // Rest-Block für Tel/E-Mail/Links — Entities dekodieren (mailto ist kodiert)
            $dec = html_entity_decode($b[5], ENT_QUOTES | ENT_HTML5);

            $out[] = [
                'name'    => $this->clean($b[1]),
                'strasse' => $strasse,
                'plz'     => trim($b[3]),
                'ort'     => $this->clean($b[4]),
                'telefon' => preg_match('~tel:\+?([0-9 /\-]+)~', $dec, $t) ? trim($t[1]) : null,
                'email'   => preg_match('~mailto:([^"\'\s<>]+)~', $dec, $e) ? trim($e[1]) : null,
                'website' => $this->linkNear($dec, 'Website'),
                'termin'  => $this->linkNear($dec, 'Termin'),
            ];
        }
        return $out;
    }

    private function linkNear(string $html, string $kw): ?string
    {
        if (preg_match('~<a[^>]+href="(https?://[^"]+)"[^>]*>[^<]*'.preg_quote($kw, '~').'~i', $html, $m)) {
            return $m[1];
        }
        return null;
    }

    private function clean(string $s): string
    {
        $s = trim(html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5));
        if (! mb_check_encoding($s, 'UTF-8')) {
            $s = mb_convert_encoding($s, 'UTF-8', 'Windows-1252');
        }
        return $s;
    }

    private function title(string $html): ?string
    {
        return preg_match('~<title[^>]*>(.*?)</title>~is', $html, $m) ? trim(html_entity_decode(strip_tags($m[1]))) : null;
    }

    /** Gemeinde-Match: PLZ-exakt (primär), sonst Ortsname mit PLZ-Region-Disambiguierung. */
    private function matchGemeinde(?string $ort, ?string $plz, array $gIndex, array $plzLandIds, array $plzIndex): ?array
    {
        // 1) Exaktes PLZ-Match (präziseste AGS-Zuordnung)
        if ($plz && isset($plzIndex[$plz])) {
            return $plzIndex[$plz];
        }

        // 2) Fallback: Ortsname + PLZ-Region
        $cands = $gIndex[$this->norm($ort)] ?? [];
        if (! $cands) return null;
        if (count($cands) === 1) return $cands[0];

        $allowed = ($plz && isset($plzLandIds[$plz[0]])) ? $plzLandIds[$plz[0]] : [];
        if ($allowed) {
            foreach ($cands as $c) {
                if (in_array($c[2], $allowed, true)) return $c;
            }
        }
        return $cands[0];
    }

    /** PLZ-Anfangsziffer => erlaubte Bundesland-IDs (grobe Leitzonen, zur Dublettenfilterung). */
    private function plzLandIds(): array
    {
        $byName = \App\Models\Bundesland::pluck('id', 'name');
        $zones = [
            '0' => ['Sachsen', 'Sachsen-Anhalt', 'Thüringen', 'Brandenburg'],
            '1' => ['Berlin', 'Brandenburg', 'Mecklenburg-Vorpommern'],
            '2' => ['Hamburg', 'Schleswig-Holstein', 'Niedersachsen', 'Bremen'],
            '3' => ['Niedersachsen', 'Hessen', 'Nordrhein-Westfalen', 'Thüringen'],
            '4' => ['Nordrhein-Westfalen', 'Niedersachsen'],
            '5' => ['Nordrhein-Westfalen', 'Rheinland-Pfalz', 'Saarland'],
            '6' => ['Hessen', 'Rheinland-Pfalz', 'Saarland', 'Baden-Württemberg', 'Bayern'],
            '7' => ['Baden-Württemberg', 'Rheinland-Pfalz'],
            '8' => ['Bayern', 'Baden-Württemberg'],
            '9' => ['Bayern', 'Thüringen'],
        ];
        $out = [];
        foreach ($zones as $d => $names) {
            $out[$d] = array_values(array_filter(array_map(fn ($n) => $byName[$n] ?? null, $names)));
        }
        return $out;
    }

    private function norm(?string $s): string
    {
        if (! $s) return '';
        $s = Str::lower($s);
        $s = preg_split('/[(,]/', $s)[0];
        $s = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $s);
        $s = preg_replace('/\b(landkreis|kreis|staedteregion|stadt|kreisfreie|hansestadt|landeshauptstadt)\b/u', '', $s);
        $s = preg_replace('/[^a-z0-9]+/u', '', $s);
        return trim($s);
    }
}
