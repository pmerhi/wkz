<?php

namespace App\Console\Commands;

use App\Models\CrawlSeite;
use App\Models\ExtraktZulassungsstelle;
use App\Models\Wettbewerber;
use App\Support\AgsMatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Extraktor für kennzeichenking.de (und Spiegel wunschkennzeichen-reservierung.de):
 * parst das strukturierte GovernmentOffice-JSON-LD je Stelle. AGS via PLZ.
 * INTERN — keine Veröffentlichung ohne anwaltliche Freigabe.
 */
class ExtractKennzeichenking extends Command
{
    protected $signature = 'extract:kennzeichenking
        {--domain=kennzeichenking.de : kennzeichenking.de oder Spiegel}
        {--skip-existing : Bereits extrahierte URLs überspringen (Resume)}
        {--limit=50} {--delay=600}';

    protected $description = 'Extrahiert Zulassungsstellen von kennzeichenking.de via JSON-LD (AGS-verknüpft, intern).';

    private string $ua = 'WunschkennzeichenPortal-Research/1.0 (interne Wettbewerbsanalyse)';

    public function handle(): int
    {
        $domain = $this->option('domain');
        $w = Wettbewerber::where('domain', $domain)->first();
        if (! $w) { $this->error("Wettbewerber $domain fehlt."); return self::FAILURE; }

        $matcher = new AgsMatcher();
        $urls = $this->stelleUrls($w->url);
        if (! $urls) { $this->error('Keine Stelle-URLs aus Sitemap.'); return self::FAILURE; }
        $this->info('Stelle-URLs: '.count($urls).' — verarbeite '.min((int) $this->option('limit'), count($urls)));

        $delay = (int) $this->option('delay') * 1000;
        $skipExisting = (bool) $this->option('skip-existing');
        $done = $skipExisting
            ? ExtraktZulassungsstelle::where('wettbewerber_id', $w->id)->pluck('quelle_url')->flip()
            : collect();
        $offices = 0; $matched = 0; $i = 0;

        foreach (array_slice($urls, 0, (int) $this->option('limit')) as $url) {
            if ($skipExisting && $done->has($url)) { continue; }
            if ((++$i % 50) === 0) { gc_collect_cycles(); }
            try { $res = Http::timeout(30)->withHeaders(['User-Agent' => $this->ua])->get($url); }
            catch (\Throwable) { $this->warn("  Fehler $url"); continue; }
            if (! $res->successful()) { usleep($delay); continue; }
            $html = $res->body();

            $seite = CrawlSeite::updateOrCreate(
                ['wettbewerber_id' => $w->id, 'url_hash' => sha1($url)],
                ['url' => $url, 'http_status' => $res->status(), 'content_type' => $res->header('Content-Type'),
                 'titel' => $this->title($html), 'html' => $html, 'abgerufen_am' => now()],
            );

            $o = $this->parseGovernmentOffice($html);
            if (! $o) { $this->line("  (kein GovernmentOffice) $url"); usleep($delay); continue; }

            try {
                $hit = $matcher->match($o['ort'], $o['plz']);
                // Dedupe über physische Identität (PLZ+Ort), nicht über URL/Name
                // (kennzeichen.click-Netz hat teils mehrere URLs/Namen je Stelle).
                $key = ($o['plz'] && $o['ort'])
                    ? ['wettbewerber_id' => $w->id, 'plz' => $o['plz'], 'ort' => mb_substr((string) $o['ort'], 0, 255)]
                    : ['wettbewerber_id' => $w->id, 'name' => mb_substr($o['name'], 0, 255)];
                ExtraktZulassungsstelle::updateOrCreate(
                    $key,
                    [
                        'crawl_seite_id' => $seite->id, 'quelle_url' => $url,
                        'name' => mb_substr($o['name'], 0, 255),
                        'strasse' => mb_substr((string) $o['strasse'], 0, 255), 'plz' => $o['plz'],
                        'ort' => mb_substr((string) $o['ort'], 0, 255),
                        'telefon' => mb_substr((string) $o['telefon'], 0, 255),
                        'email' => mb_substr((string) $o['email'], 0, 255),
                        'website' => mb_substr((string) $o['website'], 0, 255),
                        'oeffnungszeiten' => $o['oeffnungszeiten'] ?: null,
                        'gemeinde_id' => $hit[0] ?? null, 'kreis_id' => $hit[1] ?? null,
                        'roh' => $o,
                    ],
                );
                $offices++;
                if ($hit) $matched++;
            } catch (\Throwable $e) {
                $this->warn('  Skip: '.Str::limit($e->getMessage(), 80));
            }
            $this->line("  [{$res->status()}] $url");
            usleep($delay);
        }

        $this->info("Fertig. Stellen: $offices · AGS-gematcht: $matched");
        $this->comment('INTERN. Veröffentlichung erst nach anwaltlicher Freigabe (siehe recht/).');
        return self::SUCCESS;
    }

    /** Stelle-URLs aus sitemap.xml (folgt Sitemap-Index). Für das ganze kennzeichen.click-Netz. */
    private function stelleUrls(string $baseUrl): array
    {
        $base = preg_replace('#^(https?://[^/]+).*#', '$1', $baseUrl);
        $sm = $this->fetch($base.'/sitemap.xml');
        if ($sm === '') return [];

        $locs = [];
        if (str_contains($sm, '<sitemapindex')) {
            preg_match_all('~<loc>([^<]+)</loc>~', $sm, $cm);
            // bevorzugt Kind-Sitemaps mit "zulassung" im Namen, sonst alle
            $children = array_filter($cm[1], fn ($u) => str_contains($u, 'zulassung'));
            if (! $children) $children = $cm[1];
            foreach ($children as $child) {
                preg_match_all('~<loc>([^<]+)</loc>~', $this->fetch($child), $lm);
                $locs = array_merge($locs, $lm[1]);
            }
        } else {
            preg_match_all('~<loc>([^<]+)</loc>~', $sm, $lm);
            $locs = $lm[1];
        }

        // nur Stelle-Detailseiten /zulassungsstellen/{land}/{stadt} (nicht die bloßen Bundesland-Seiten)
        $out = array_filter($locs, fn ($u) => preg_match('~/zulassungsstellen/[a-z-]+/[a-z0-9-]+~', $u));
        return array_values(array_unique($out));
    }

    private function fetch(string $url): string
    {
        try { return Http::timeout(30)->withHeaders(['User-Agent' => $this->ua])->get($url)->body(); }
        catch (\Throwable) { return ''; }
    }

    private function parseGovernmentOffice(string $html): ?array
    {
        preg_match_all('~<script[^>]*application/ld\+json[^>]*>(.*?)</script>~is', $html, $m);
        foreach ($m[1] as $block) {
            $data = json_decode(trim($block), true);
            if (! is_array($data)) continue;
            $go = $this->findType($data, 'GovernmentOffice');
            if (! $go) continue;

            $addr = $go['address'] ?? [];
            $hours = [];
            foreach ($go['openingHoursSpecification'] ?? [] as $s) {
                if (! isset($s['opens'], $s['closes'])) continue;
                $day = isset($s['dayOfWeek']) ? basename((string) $s['dayOfWeek']) : null;
                $hours[] = ['day' => $day, 'opens' => $s['opens'], 'closes' => $s['closes']];
            }
            return [
                'name'            => (string) ($go['name'] ?? ''),
                'strasse'         => $addr['streetAddress'] ?? null,
                'plz'             => isset($addr['postalCode']) ? preg_replace('/\D/', '', $addr['postalCode']) : null,
                'ort'             => $addr['addressLocality'] ?? null,
                'region'          => $addr['addressRegion'] ?? null,
                'telefon'         => $go['telephone'] ?? null,
                'email'           => $go['email'] ?? null,
                'website'         => $go['url'] ?? null,
                'oeffnungszeiten' => $hours,
            ];
        }
        return null;
    }

    /** Sucht rekursiv ein Objekt mit gegebenem @type. */
    private function findType($node, string $type): ?array
    {
        if (is_array($node)) {
            if (($node['@type'] ?? null) === $type) return $node;
            foreach ($node as $v) {
                if (is_array($v)) {
                    $r = $this->findType($v, $type);
                    if ($r) return $r;
                }
            }
        }
        return null;
    }

    private function title(string $html): ?string
    {
        return preg_match('~<title[^>]*>(.*?)</title>~is', $html, $m) ? trim(html_entity_decode(strip_tags($m[1]))) : null;
    }
}
