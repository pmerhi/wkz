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
 * Extraktor für gutschild.de (HTML, Label-basiert). AGS via PLZ.
 * INTERN — keine Veröffentlichung ohne anwaltliche Freigabe.
 */
class ExtractGutschild extends Command
{
    protected $signature = 'extract:gutschild {--limit=50} {--delay=500} {--skip-existing}';

    protected $description = 'Extrahiert Zulassungsstellen von gutschild.de (HTML, AGS-verknüpft, intern).';

    private string $ua = 'WunschkennzeichenPortal-Research/1.0 (interne Wettbewerbsanalyse)';

    public function handle(): int
    {
        $w = Wettbewerber::where('domain', 'gutschild.de')->first();
        if (! $w) { $this->error('Wettbewerber fehlt.'); return self::FAILURE; }

        $matcher = new AgsMatcher();
        $urls = $this->detailUrls();
        if (! $urls) { $this->error('Keine Stelle-URLs.'); return self::FAILURE; }
        $this->info('Stelle-URLs: '.count($urls).' — verarbeite '.min((int) $this->option('limit'), count($urls)));

        $delay = (int) $this->option('delay') * 1000;
        $skip = (bool) $this->option('skip-existing');
        $done = $skip ? ExtraktZulassungsstelle::where('wettbewerber_id', $w->id)->pluck('quelle_url')->flip() : collect();
        $offices = 0; $matched = 0; $i = 0;

        foreach (array_slice($urls, 0, (int) $this->option('limit')) as $url) {
            if ($skip && $done->has($url)) continue;
            if ((++$i % 50) === 0) gc_collect_cycles();
            try { $res = Http::timeout(30)->withHeaders(['User-Agent' => $this->ua])->get($url); }
            catch (\Throwable) { $this->warn("  Fehler $url"); continue; }
            if (! $res->successful()) { usleep($delay); continue; }
            $html = $res->body();

            $seite = CrawlSeite::updateOrCreate(
                ['wettbewerber_id' => $w->id, 'url_hash' => sha1($url)],
                ['url' => $url, 'http_status' => $res->status(), 'content_type' => $res->header('Content-Type'),
                 'titel' => $this->title($html), 'html' => $html, 'abgerufen_am' => now()],
            );

            $o = $this->parse($html);
            if (! $o || ! $o['plz']) { $this->line("  (keine Daten) $url"); usleep($delay); continue; }

            try {
                $hit = $matcher->match($o['ort'], $o['plz']);
                $key = $o['ort']
                    ? ['wettbewerber_id' => $w->id, 'plz' => $o['plz'], 'ort' => mb_substr((string) $o['ort'], 0, 255)]
                    : ['wettbewerber_id' => $w->id, 'quelle_url' => $url];
                ExtraktZulassungsstelle::updateOrCreate($key, [
                    'crawl_seite_id' => $seite->id, 'quelle_url' => $url,
                    'name' => mb_substr((string) $o['name'], 0, 255),
                    'strasse' => mb_substr((string) $o['strasse'], 0, 255),
                    'plz' => $o['plz'], 'ort' => mb_substr((string) $o['ort'], 0, 255),
                    'telefon' => mb_substr((string) $o['telefon'], 0, 255),
                    'email' => mb_substr((string) $o['email'], 0, 255),
                    'oeffnungszeiten' => $o['oeffnungszeiten'] ?: null,
                    'gemeinde_id' => $hit[0] ?? null, 'kreis_id' => $hit[1] ?? null,
                    'roh' => $o,
                ]);
                $offices++;
                if ($hit) $matched++;
            } catch (\Throwable $e) { $this->warn('  Skip: '.Str::limit($e->getMessage(), 80)); }
            $this->line("  [{$res->status()}] $url");
            usleep($delay);
        }

        $this->info("Fertig. Stellen: $offices · AGS-gematcht: $matched");
        $this->comment('INTERN. Veröffentlichung erst nach anwaltlicher Freigabe (siehe recht/).');
        return self::SUCCESS;
    }

    private function detailUrls(): array
    {
        try { $xml = Http::timeout(40)->withHeaders(['User-Agent' => $this->ua])->get('https://www.gutschild.de/shop_sitemap.xml')->body(); }
        catch (\Throwable) { return []; }
        preg_match_all('~(https://www\.gutschild\.de/zulassungsstelle/[a-z0-9-]+/[a-z0-9-]+/?)~', $xml, $m);
        return array_values(array_unique($m[1]));
    }

    private function parse(string $html): ?array
    {
        $name = preg_match('~<h4>\s*Anschrift der Zulassungsstelle\s*(.+?)</h4>~i', $html, $n) ? $this->clean($n[1]) : null;

        $strasse = $plz = $ort = null;
        // Adresse zwischen Anschrift-<h4> und nächstem <h4>
        if (preg_match('~Anschrift der Zulassungsstelle[^<]*</h4>(.*?)<h4~is', $html, $a)) {
            if (preg_match('~(.*?)<br\s*/?>\s*(\d{5})\s+(.+)$~is', trim($a[1]), $b)) {
                $strasse = $this->clean($b[1]); $plz = $b[2]; $ort = $this->clean($b[3]);
            }
        }

        $telefon = $this->labelValue($html, 'Telefonnummer');
        $email = null;
        if (preg_match('~[\w.+\-]+@[\w.\-]+\.[a-z]{2,}~i', $this->labelValue($html, 'E-Mail-Adresse') ?? '', $e)) $email = $e[0];

        // Öffnungszeiten aus bereinigtem Text
        $txt = $this->plain($html);
        $hours = [];
        if (($p = mb_stripos($txt, 'Öffnungszeiten')) !== false) {
            $slice = mb_substr($txt, $p, 500);
            preg_match_all('~(Montag|Dienstag|Mittwoch|Donnerstag|Freitag|Samstag|Sonntag)\s*:?\s*(\d{1,2})[.:](\d{2})\s*[-–]\s*(\d{1,2})[.:](\d{2})~u', $slice, $hm, PREG_SET_ORDER);
            foreach ($hm as $r) $hours[] = ['day' => $r[1], 'opens' => $r[2].':'.$r[3], 'closes' => $r[4].':'.$r[5]];
        }

        if (! $plz && ! $name) return null;
        return compact('name', 'strasse', 'plz', 'ort', 'telefon', 'email') + ['oeffnungszeiten' => $hours];
    }

    private function labelValue(string $html, string $label): ?string
    {
        if (preg_match('~'.preg_quote($label, '~').':\s*</div>\s*<div[^>]*>(.*?)</div>~is', $html, $m)) {
            return $this->clean($m[1]);
        }
        return null;
    }

    private function clean(string $s): string
    {
        return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($s))));
    }

    private function plain(string $html): string
    {
        $html = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', ' ', $html);
        return preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html)));
    }

    private function title(string $html): ?string
    {
        return preg_match('~<title[^>]*>(.*?)</title>~is', $html, $m) ? trim(html_entity_decode(strip_tags($m[1]))) : null;
    }
}
