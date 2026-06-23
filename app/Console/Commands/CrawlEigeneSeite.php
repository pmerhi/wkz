<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Crawlt die eigene Seite wunschkennzeichen-reservieren.de (First-Party) anhand der
 * sitemap.xml und legt das rohe HTML je Seite als Datei ab. Schonend: identifizierender
 * User-Agent, Rate-Limit, robots-konform (überspringt /category/ und /author/),
 * wiederaufsetzbar (bereits geladene Seiten werden übersprungen).
 */
class CrawlEigeneSeite extends Command
{
    protected $signature = 'crawl:eigene
        {--typ=* : nur diese Pfad-Typen (zulassungsstelle, kennzeichen, wunschkennzeichen)}
        {--delay=1200 : Pause zwischen Requests in ms}
        {--limit=0 : max. Seiten (0 = alle)}';

    protected $description = 'Crawlt wunschkennzeichen-reservieren.de (eigene Seite) per Sitemap, speichert HTML.';

    private const BASE = 'https://www.wunschkennzeichen-reservieren.de';
    private const UA = 'WunschkennzeichenPortal-Research/1.0 (eigene Seite; Kontakt: patrick@merhi.de)';
    private const DISALLOW = ['/category/', '/author/'];

    public function handle(): int
    {
        $dir = storage_path('app/crawl/wkr');
        if (! is_dir($dir)) mkdir($dir, 0775, true);

        // Sitemap laden
        try {
            $xml = Http::withHeaders(['User-Agent' => self::UA])->timeout(60)->get(self::BASE.'/sitemap.xml')->body();
        } catch (\Throwable $e) {
            $this->error('Sitemap nicht erreichbar: '.$e->getMessage());
            return self::FAILURE;
        }
        preg_match_all('~<loc>\s*([^<]+?)\s*</loc>~', $xml, $m);
        $urls = array_values(array_unique($m[1] ?? []));
        $this->info('Sitemap-URLs: '.count($urls));

        $typen = $this->option('typ');
        $delay = (int) $this->option('delay') * 1000;       // µs
        $limit = (int) $this->option('limit');

        $geladen = 0; $uebersprungen = 0; $fehler = 0; $verarbeitet = 0;
        foreach ($urls as $url) {
            $path = parse_url($url, PHP_URL_PATH) ?: '/';
            foreach (self::DISALLOW as $d) {
                if (str_contains($path, $d)) { $uebersprungen++; continue 2; }
            }
            $typ = $this->typVon($path);
            if ($typen && ! in_array($typ, $typen, true)) { $uebersprungen++; continue; }

            $file = $dir.'/'.$this->dateiname($path);
            if (is_file($file) && filesize($file) > 500) { $uebersprungen++; continue; }   // resume

            try {
                $res = Http::withHeaders(['User-Agent' => self::UA])->timeout(30)->get($url);
                if ($res->successful()) {
                    file_put_contents($file, $res->body());
                    $geladen++;
                } else {
                    $fehler++;
                }
            } catch (\Throwable $e) {
                $fehler++;
            }

            $verarbeitet++;
            if ($verarbeitet % 50 === 0) {
                $this->line("  … $verarbeitet verarbeitet (geladen $geladen, übersprungen $uebersprungen, Fehler $fehler)");
            }
            if ($limit && $geladen >= $limit) break;
            usleep($delay);
        }

        $this->info("Fertig. Geladen: $geladen · übersprungen: $uebersprungen · Fehler: $fehler · Ablage: $dir");
        return self::SUCCESS;
    }

    private function typVon(string $path): string
    {
        $seg = explode('/', trim($path, '/'));
        return $seg[0] ?? 'root';
    }

    private function dateiname(string $path): string
    {
        $slug = trim($path, '/') ?: 'home';
        return Str::slug(str_replace('/', '__', $slug)).'.html';
    }
}
