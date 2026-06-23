<?php

namespace App\Console\Commands;

use App\Models\CrawlSeite;
use App\Models\Wettbewerber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CrawlWettbewerber extends Command
{
    protected $signature = 'crawl:wettbewerber
        {--wettbewerber= : Nur diese Domain crawlen (sonst alle)}
        {--start= : Einstiegs-URL überschreiben (z.B. Verzeichnis-Seite)}
        {--max=25 : Maximale Seiten pro Wettbewerber}
        {--delay=1500 : Pause zwischen Anfragen in ms (Rate-Limit)}';

    protected $description = 'Archiviert Wettbewerber-Seiten (robots.txt-konform, rate-limited) für die interne Analyse.';

    private string $ua = 'WunschkennzeichenPortal-Research/1.0 (interne Wettbewerbsanalyse)';

    public function handle(): int
    {
        $query = Wettbewerber::query();
        if ($d = $this->option('wettbewerber')) {
            $query->where('domain', $d);
        }
        $competitors = $query->get();
        $max   = (int) $this->option('max');
        $delay = (int) $this->option('delay') * 1000; // µs

        foreach ($competitors as $w) {
            $this->info("== {$w->name} ({$w->domain}) ==");
            $start = $this->option('start')
                ?: ($this->normalizeUrl($w->url, $w->url) ?? ('https://'.$w->domain.'/'));
            $host  = parse_url($start, PHP_URL_HOST);
            $disallow = $this->robotsDisallow($start);

            $queue = [$start];
            $seen  = [];
            $count = 0;

            while ($queue && $count < $max) {
                $url = array_shift($queue);
                $key = $this->urlKey($url);
                if (isset($seen[$key])) continue;
                $seen[$key] = true;

                if ($this->isDisallowed($url, $disallow)) {
                    $this->line("  robots-disallow: $url");
                    continue;
                }

                try {
                    $res = Http::timeout(30)->withHeaders(['User-Agent' => $this->ua])
                        ->get($url);
                } catch (\Throwable $e) {
                    $this->warn("  Fehler: ".Str::limit($e->getMessage(), 80));
                    continue;
                }

                $ct = $res->header('Content-Type');
                $html = $res->body();
                $isHtml = str_contains((string) $ct, 'text/html');

                CrawlSeite::updateOrCreate(
                    ['wettbewerber_id' => $w->id, 'url_hash' => sha1($url)],
                    [
                        'url'          => $url,
                        'http_status'  => $res->status(),
                        'content_type' => $ct,
                        'titel'        => $isHtml ? $this->title($html) : null,
                        'html'         => $isHtml ? $html : null,
                        'text'         => $isHtml ? $this->plainText($html) : null,
                        'inhalt_hash'  => sha1($html),
                        'abgerufen_am' => now(),
                    ]
                );
                $count++;
                $this->line("  [{$res->status()}] $url");

                if ($isHtml && $res->successful()) {
                    foreach ($this->links($html, $url, $host) as $link) {
                        if (! isset($seen[$this->urlKey($link)])) {
                            $queue[] = $link;
                        }
                    }
                }

                usleep($delay);
            }

            $this->info("  gespeichert: $count Seiten");
        }

        $this->info('Fertig. Archiv gesamt: '.CrawlSeite::count().' Seiten.');
        return self::SUCCESS;
    }

    /** Disallow-Pfade für User-agent * aus robots.txt. */
    private function robotsDisallow(string $start): array
    {
        $robots = rtrim(preg_replace('#^(https?://[^/]+).*#', '$1', $start), '/').'/robots.txt';
        try {
            $body = Http::timeout(15)->withHeaders(['User-Agent' => $this->ua])->get($robots)->body();
        } catch (\Throwable) {
            return [];
        }
        $rules = []; $active = false;
        foreach (preg_split('/\r?\n/', (string) $body) as $line) {
            $line = trim($line);
            if (stripos($line, 'User-agent:') === 0) {
                $active = trim(substr($line, 11)) === '*';
            } elseif ($active && stripos($line, 'Disallow:') === 0) {
                $p = trim(substr($line, 9));
                if ($p !== '') $rules[] = $p;
            }
        }
        return $rules;
    }

    private function isDisallowed(string $url, array $disallow): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        foreach ($disallow as $rule) {
            if (str_starts_with($path, rtrim($rule, '*'))) return true;
        }
        return false;
    }

    private function links(string $html, string $base, ?string $host): array
    {
        preg_match_all('/href=["\']([^"\'#]+)/i', $html, $m);
        $out = [];
        foreach ($m[1] as $href) {
            if (preg_match('#^(mailto:|tel:|javascript:|data:)#i', $href)) continue;
            $abs = $this->normalizeUrl($href, $base);
            if (! $abs) continue;
            if (parse_url($abs, PHP_URL_HOST) !== $host) continue;
            if (preg_match('#\.(jpg|jpeg|png|gif|webp|svg|pdf|zip|css|js|ico|woff2?|webmanifest|json|xml|txt|map|mp4|webp)$#i', parse_url($abs, PHP_URL_PATH) ?? '')) continue;
            $out[] = $abs;
        }
        return array_unique($out);
    }

    private function normalizeUrl(string $href, string $base): ?string
    {
        $href = trim($href);
        if ($href === '') return null;
        if (preg_match('#^https?://#i', $href)) return $this->stripFragment($href);
        $b = parse_url($base);
        if (! isset($b['scheme'], $b['host'])) return null;
        $origin = $b['scheme'].'://'.$b['host'];
        if (str_starts_with($href, '//')) return $this->stripFragment($b['scheme'].':'.$href);
        if (str_starts_with($href, '/')) return $this->stripFragment($origin.$href);
        $dir = isset($b['path']) ? preg_replace('#/[^/]*$#', '/', $b['path']) : '/';
        return $this->stripFragment($origin.$dir.$href);
    }

    private function stripFragment(string $url): string
    {
        return preg_replace('/#.*$/', '', $url);
    }

    private function urlKey(string $url): string
    {
        return sha1(rtrim($url, '/'));
    }

    private function title(string $html): ?string
    {
        return preg_match('#<title[^>]*>(.*?)</title>#is', $html, $m)
            ? trim(html_entity_decode(strip_tags($m[1]))) : null;
    }

    private function plainText(string $html): string
    {
        $html = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', ' ', $html);
        $text = html_entity_decode(strip_tags($html));
        return trim(preg_replace('/\s+/', ' ', $text));
    }
}
