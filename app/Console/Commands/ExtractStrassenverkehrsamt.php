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
 * Extraktor für strassenverkehrsamt.de (STVA). HTML-basiert (kein JSON-LD):
 * Detail-Unterseiten /lokal/{stadt}/kfz-zulassungsstelle/{behörde}. AGS via PLZ.
 * INTERN — keine Veröffentlichung ohne anwaltliche Freigabe.
 */
class ExtractStrassenverkehrsamt extends Command
{
    protected $signature = 'extract:strassenverkehrsamt {--limit=50} {--delay=500} {--skip-existing}';

    protected $description = 'Extrahiert Zulassungsstellen von strassenverkehrsamt.de (HTML, AGS-verknüpft, intern).';

    private string $ua = 'WunschkennzeichenPortal-Research/1.0 (interne Wettbewerbsanalyse)';

    public function handle(): int
    {
        $w = Wettbewerber::where('domain', 'strassenverkehrsamt.de')->first();
        if (! $w) { $this->error('Wettbewerber fehlt.'); return self::FAILURE; }

        $matcher = new AgsMatcher();
        $urls = $this->detailUrls();
        if (! $urls) { $this->error('Keine Detail-URLs aus Sitemap.'); return self::FAILURE; }
        $this->info('Detail-Seiten: '.count($urls).' — verarbeite '.min((int) $this->option('limit'), count($urls)));

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
                ExtraktZulassungsstelle::updateOrCreate(
                    ['wettbewerber_id' => $w->id, 'quelle_url' => $url],
                    [
                        'crawl_seite_id' => $seite->id,
                        'name' => mb_substr((string) $o['name'], 0, 255),
                        'strasse' => mb_substr((string) $o['strasse'], 0, 255),
                        'plz' => $o['plz'], 'ort' => mb_substr((string) $o['ort'], 0, 255),
                        'telefon' => mb_substr((string) $o['telefon'], 0, 255),
                        'email' => mb_substr((string) $o['email'], 0, 255),
                        'oeffnungszeiten' => $o['oeffnungszeiten'] ?: null,
                        'gemeinde_id' => $hit[0] ?? null, 'kreis_id' => $hit[1] ?? null,
                        'roh' => $o,
                    ],
                );
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
        try { $xml = Http::timeout(40)->withHeaders(['User-Agent' => $this->ua])->get('https://www.strassenverkehrsamt.de/sitemap.xml')->body(); }
        catch (\Throwable) { return []; }
        preg_match_all('~<loc>([^<]*/lokal/[^<]+/kfz-zulassungsstelle/[a-z0-9-]+)</loc>~', $xml, $m);
        return array_values(array_unique($m[1]));
    }

    private function parse(string $html): ?array
    {
        // Adresse: …<br> Straße <br> PLZ Ort </p>
        $strasse = $plz = $ort = null;
        if (preg_match('~<br\s*/?>\s*([^<]+?)\s*<br\s*/?>\s*(\d{5})\s+([^<]+?)\s*</p>~iu', $html, $a)) {
            $strasse = $this->clean($a[1]); $plz = $a[2]; $ort = $this->clean($a[3]);
        }

        $name = null;
        if (preg_match('~<title[^>]*>([^|<]+)~i', $html, $t)) $name = trim(html_entity_decode($t[1]));

        $telefon = preg_match('~Fon:\s*([0-9 /+()\-]+)~', $html, $p) ? trim($p[1]) : null;
        $email = preg_match('~E-Mail:\s*([^\s<]+@[^\s<]+)~', $html, $e) ? trim($e[1]) : null;

        // Öffnungszeiten je Tag
        $hours = [];
        if (preg_match('~Öffnungszeiten</h3>\s*<p>(.*?)</p>~is', $html, $h)) {
            preg_match_all('~(Montag|Dienstag|Mittwoch|Donnerstag|Freitag|Samstag|Sonntag):\s*(\d{1,2}:\d{2})\s*[–\-]\s*(\d{1,2}:\d{2})~u', $h[1], $hm, PREG_SET_ORDER);
            foreach ($hm as $r) $hours[] = ['day' => $r[1], 'opens' => $r[2], 'closes' => $r[3]];
        }

        if (! $plz && ! $name) return null;
        return compact('name', 'strasse', 'plz', 'ort', 'telefon', 'email') + ['oeffnungszeiten' => $hours];
    }

    private function clean(string $s): string
    {
        return trim(html_entity_decode(strip_tags($s)));
    }

    private function title(string $html): ?string
    {
        return preg_match('~<title[^>]*>(.*?)</title>~is', $html, $m) ? trim(html_entity_decode(strip_tags($m[1]))) : null;
    }
}
