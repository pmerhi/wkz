<?php

namespace App\Console\Commands;

use App\Models\Zulassungsstelle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * Lädt je Stelle die offizielle Öffnungszeiten-Quellseite + passende Unterseiten
 * mit realistischen Browser-Headern (umgeht naive Bot-Sperren) und speichert den
 * sichtbaren Text als Snapshot-Datei. Diese Snapshots liest danach die LLM-
 * Extraktion – ohne erneuten Live-Abruf (schneller, robuster).
 */
class OzSnapshot extends Command
{
    protected $signature = 'oz:snapshot
        {--limit=0 : maximal so viele}
        {--ids= : nur diese IDs (kommagetrennt)}
        {--only-missing : nur Stellen ohne vorhandenen Snapshot}';

    protected $description = 'Erstellt Text-Snapshots der offiziellen Seiten (+ Unterseiten) für die Extraktion.';

    private const UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';
    private const SUB_KEYWORDS = ['oeffnungszeit', 'öffnungszeit', 'sprechzeit', 'kontakt', 'kfz-zulassung', 'zulassung'];
    private const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

    public function handle(): int
    {
        $dir = storage_path('app/snapshots');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $query = Zulassungsstelle::whereNull('parent_id')->whereNotNull('oeffnungszeiten_url');
        if ($ids = $this->option('ids')) {
            $query->whereIn('id', array_filter(array_map('trim', explode(',', $ids))));
        }
        if (($limit = (int) $this->option('limit')) > 0) {
            $query->limit($limit);
        }

        $ok = 0; $mitZeiten = 0; $fehler = 0;
        foreach ($query->cursor() as $s) {
            $datei = $dir.'/'.$s->id.'.txt';
            if ($this->option('only-missing') && is_file($datei)) {
                continue;
            }

            $html = $this->lade($s->oeffnungszeiten_url);
            $text = $html ? $this->sichtbarerText($html) : '';

            // Relevante Unterseiten dazuladen (max 3), wenn auf der Hauptseite keine Zeiten stehen.
            if ($html && ! $this->hatZeiten($text)) {
                foreach ($this->subLinks($html, $s->oeffnungszeiten_url) as $sub) {
                    $subHtml = $this->lade($sub);
                    if ($subHtml) {
                        $text .= "\n\n--- Unterseite: $sub ---\n".$this->sichtbarerText($subHtml);
                    }
                    if ($this->hatZeiten($text)) {
                        break;
                    }
                }
            }

            // Fallback Headless-Chrome (rendert JS) – wenn HTTP nichts/keine Zeiten lieferte.
            if (! $this->hatZeiten($text)) {
                $gerendert = $this->ladeHeadless($s->oeffnungszeiten_url);
                if ($gerendert && strlen($gerendert) > strlen($text)) {
                    $text = $gerendert;
                }
            }

            if ($text === '') {
                $fehler++;
                $this->warn("  ✗ {$s->name}: {$s->oeffnungszeiten_url}");
                continue;
            }

            $text = Str::limit($text, 16000, '');
            file_put_contents($datei, "URL: {$s->oeffnungszeiten_url}\nSTELLE: {$s->name} ({$s->plz} {$s->ort})\n\n".$text);
            $ok++;
            if ($this->hatZeiten($text)) {
                $mitZeiten++;
            }
        }

        $this->info("Snapshots: $ok · davon mit Zeit-Muster: $mitZeiten · Abruf-Fehler: $fehler");
        return self::SUCCESS;
    }

    private function lade(string $url): ?string
    {
        $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5);   // &amp; → &
        foreach ([true, false] as $verify) {   // 2. Versuch ohne SSL-Verify
            try {
                $res = Http::withHeaders([
                    'User-Agent'      => self::UA,
                    'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'de-DE,de;q=0.9,en;q=0.8',
                ])->withOptions(['verify' => $verify])->timeout(20)->get($url);
                if ($res->successful()) {
                    return $res->body();
                }
            } catch (\Throwable $e) {
                // nächster Versuch
            }
        }

        return null;
    }

    /** Seite mit echtem Chrome rendern (JS) und sichtbaren Text zurückgeben. */
    private function ladeHeadless(string $url): ?string
    {
        if (! is_file(self::CHROME)) {
            return null;
        }
        $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5);
        try {
            $res = Process::timeout(45)->run([
                self::CHROME, '--headless=new', '--disable-gpu', '--no-sandbox',
                '--hide-scrollbars', '--virtual-time-budget=8000',
                '--user-agent='.self::UA, '--dump-dom', $url,
            ]);
            if (! $res->successful()) {
                return null;
            }
            $text = $this->sichtbarerText($res->output());

            return $this->hatZeiten($text) || strlen($text) > 200 ? $text : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function sichtbarerText(string $html): string
    {
        $t = preg_replace('#<(script|style|nav|footer|svg)\b[^>]*>.*?</\1>#is', ' ', $html);
        $t = html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/[ \t]*\n[ \t]*(\n)?/', "\n", preg_replace('/[ \t]+/u', ' ', $t)));
    }

    private function hatZeiten(string $text): bool
    {
        return (bool) preg_match('/\d{1,2}[:.]\d{2}\s*(?:-|–|bis)\s*\d{1,2}[:.]\d{2}/u', $text);
    }

    /** Links zu Öffnungszeiten-/Kontakt-Unterseiten (gleiche Domain). */
    private function subLinks(string $html, string $basis): array
    {
        $host = parse_url($basis, PHP_URL_HOST);
        $scheme = parse_url($basis, PHP_URL_SCHEME) ?: 'https';
        $treffer = [];
        if (! preg_match_all('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $m, PREG_SET_ORDER)) {
            return [];
        }
        foreach ($m as $a) {
            $href = $a[1];
            $label = mb_strtolower(strip_tags($a[2]).' '.$href);
            $relevant = false;
            foreach (self::SUB_KEYWORDS as $kw) {
                if (str_contains($label, $kw)) {
                    $relevant = true;
                    break;
                }
            }
            if (! $relevant || str_starts_with($href, '#') || str_starts_with($href, 'mailto')) {
                continue;
            }
            // Absolut machen.
            if (str_starts_with($href, 'http')) {
                if (parse_url($href, PHP_URL_HOST) !== $host) {
                    continue;
                }
                $url = $href;
            } elseif (str_starts_with($href, '/')) {
                $url = $scheme.'://'.$host.$href;
            } else {
                continue;
            }
            $treffer[$url] = true;
            if (count($treffer) >= 3) {
                break;
            }
        }

        return array_keys($treffer);
    }
}
