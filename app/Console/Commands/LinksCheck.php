<?php

namespace App\Console\Commands;

use App\Models\LinkCheck;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Sammelt alle externen Links aus den Inhalten (Ratgeber-Markdown, Blade-Views,
 * Daten-Dateien) – optional auch aus der DB – prüft ihren HTTP-Status und legt
 * das Ergebnis in `link_checks` ab. Auswertung im Admin unter „Link-Check".
 */
class LinksCheck extends Command
{
    protected $signature = 'links:check
        {--mit-db : Auch externe Links aus der DB (Zulassungsstellen-Website/Termin-URL) prüfen}';

    protected $description = 'Prüft alle externen Links (Ratgeber, Views, optional DB) auf tote Verweise.';

    /** Nicht prüfen: eigene Domain, Namespace-URIs, dynamische Blade-URLs. */
    private const IGNORE_HOSTS = ['schema.org', 'www.w3.org', 'localhost', '127.0.0.1'];

    private const BROWSER_UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

    public function handle(): int
    {
        $eigeneHosts = array_filter([parse_url((string) config('app.url'), PHP_URL_HOST), 'wunsch']);

        // 1) Links einsammeln: url => [quellen]
        $links = [];
        $this->sammleAusDateien($links);
        if ($this->option('mit-db')) {
            $this->sammleAusDb($links);
        }

        // Eigene/ignorierte Hosts herausfiltern
        $links = array_filter($links, function ($q, $url) use ($eigeneHosts) {
            $host = parse_url($url, PHP_URL_HOST) ?: '';
            foreach (array_merge($eigeneHosts, self::IGNORE_HOSTS) as $ign) {
                if ($ign && Str::contains($host, $ign)) return false;
            }
            return (bool) $host;
        }, ARRAY_FILTER_USE_BOTH);

        $this->info(count($links).' eindeutige externe Links gefunden. Prüfe …');

        // 2) In Blöcken nebenläufig prüfen
        $urls = array_keys($links);
        $defekt = 0;
        $hashes = [];
        foreach (array_chunk($urls, 15) as $block) {
            $antworten = Http::pool(fn ($pool) => array_map(
                fn ($u) => $pool->as($u)->timeout(20)->withoutVerifying()
                    ->withHeaders(['User-Agent' => self::BROWSER_UA])->get($u),
                $block
            ));

            foreach ($block as $url) {
                [$status, $ok, $fehler] = $this->auswerten($antworten[$url] ?? null);
                if (! $ok) $defekt++;
                $hashes[] = LinkCheck::hashFor($url);
                LinkCheck::updateOrCreate(
                    ['url_hash' => LinkCheck::hashFor($url)],
                    [
                        'url'         => Str::limit($url, 1020, ''),
                        'quellen'     => implode("\n", array_slice(array_unique($links[$url]), 0, 40)),
                        'status'      => $status,
                        'ok'          => $ok,
                        'fehler'      => $fehler,
                        'geprueft_at' => now(),
                    ],
                );
                $this->line(sprintf('  %s %s', $ok ? '✓' : '✗ '.$status, $url));
            }
        }

        // 3) Nicht mehr vorkommende Links entfernen – jeder Lauf ist ein voller Schnappschuss.
        if ($hashes) {
            LinkCheck::whereNotIn('url_hash', $hashes)->delete();
        }

        $this->newLine();
        $this->info("Fertig. ".count($urls)." geprüft, {$defekt} auffällig.");
        return self::SUCCESS;
    }

    /** Wertet eine Pool-Antwort aus → [status, ok, fehler]. */
    private function auswerten($resp): array
    {
        if (! $resp instanceof \Illuminate\Http\Client\Response) {
            $msg = $resp instanceof \Throwable ? $resp->getMessage() : 'keine Antwort';
            return [null, false, Str::limit('Verbindungsfehler: '.$msg, 480, '')];
        }
        $status = $resp->status();
        // 2xx/3xx = ok. 401/403/405/429/999 = wahrscheinlich Bot-Sperre, kein echter Bruch.
        if ($status >= 200 && $status < 400) {
            return [$status, true, null];
        }
        if (in_array($status, [401, 403, 405, 429, 999])) {
            return [$status, false, 'evtl. Bot-Sperre (HTTP '.$status.') – bitte manuell prüfen'];
        }
        return [$status, false, 'HTTP '.$status];
    }

    /** Externe URLs aus Ratgeber-Markdown, Views und Daten-Dateien einsammeln. */
    private function sammleAusDateien(array &$links): void
    {
        $verzeichnisse = [base_path('content'), resource_path('views'), resource_path('data')];
        foreach ($verzeichnisse as $dir) {
            if (! is_dir($dir)) continue;
            foreach (File::allFiles($dir) as $datei) {
                if (! in_array($datei->getExtension(), ['md', 'php', 'html', 'blade'])) continue;
                if ($datei->getFilename() === 'welcome.blade.php') continue; // ungenutzte Laravel-Default-View
                $inhalt = File::get($datei->getPathname());
                if (! preg_match_all('#https?://[^\s"\'\)\]<>{}]+#', $inhalt, $treffer, PREG_OFFSET_CAPTURE)) continue;
                $quelle = str_replace(base_path().'/', '', $datei->getPathname());
                foreach ($treffer[0] as [$roh, $offset]) {
                    // Folgt direkt ein '{' (z.B. Kartenkacheln .../{z}/{x}/{y}.png), ist es ein Template → überspringen.
                    if (($inhalt[$offset + strlen($roh)] ?? '') === '{') continue;
                    $url = rtrim($roh, '.,;:');
                    if (Str::contains($url, ['{', '}', '$', '\\'])) continue;             // dynamische URLs
                    if (Str::endsWith($url, ['=', '?', '/photos/maxmustermann/123456789'])) continue; // Platzhalter
                    $links[$url][] = $quelle;
                }
            }
        }
    }

    /** Externe Website-/Termin-Links aus den Zulassungsstellen einsammeln. */
    private function sammleAusDb(array &$links): void
    {
        \App\Models\Zulassungsstelle::query()
            ->whereNotNull('website')->orWhereNotNull('termin_url')
            ->select('id', 'website', 'termin_url')->chunk(500, function ($stellen) use (&$links) {
                foreach ($stellen as $s) {
                    foreach (array_filter([$s->website, $s->termin_url]) as $url) {
                        if (Str::startsWith($url, 'http')) {
                            $links[rtrim($url, '.,;:')][] = 'DB: Zulassungsstelle #'.$s->id;
                        }
                    }
                }
            });
    }
}
