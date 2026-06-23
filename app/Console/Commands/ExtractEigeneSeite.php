<?php

namespace App\Console\Commands;

use App\Models\EigeneStelle;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Extrahiert aus den gecrawlten /zulassungsstelle/{ort}/-Seiten der eigenen Seite
 * die aktuellen Stammdaten: Name, Anschrift, Telefon/Fax/E-Mail, Öffnungszeiten,
 * echte Termin-URL (+ erkanntes Terminsystem), Funnel-/Affiliate-Link inkl. Kürzel
 * (symbol=) und Zulassungsbezirk → Tabelle `eigene_stelle`.
 */
class ExtractEigeneSeite extends Command
{
    protected $signature = 'extract:eigene {--dry}';

    protected $description = 'Extrahiert Stammdaten aus den gecrawlten eigenen Zulassungsstellen-Seiten.';

    private const TAGE = ['Mo' => 'Monday', 'Di' => 'Tuesday', 'Mi' => 'Wednesday', 'Do' => 'Thursday', 'Fr' => 'Friday', 'Sa' => 'Saturday', 'So' => 'Sunday'];

    public function handle(): int
    {
        $dir = storage_path('app/crawl/wkr');
        $files = glob($dir.'/zulassungsstelle-*.html') ?: [];
        $this->info('Gecrawlte Zulassungsstellen-Seiten: '.count($files));

        $n = 0; $mitTermin = 0; $mitHours = 0;
        foreach ($files as $file) {
            $html = file_get_contents($file);
            $slug = preg_replace('/^zulassungsstelle-|\.html$/', '', basename($file));
            $data = $this->parse($html, $slug);
            if (! $data) continue;
            $n++;
            if ($data['termin_url']) $mitTermin++;
            if ($data['oeffnungszeiten']) $mitHours++;
            if (! $this->option('dry')) {
                EigeneStelle::updateOrCreate(['url' => $data['url']], $data);
            }
        }

        $this->info(($this->option('dry') ? '[DRY] ' : '')."Extrahiert: $n · mit Termin-URL: $mitTermin · mit Öffnungszeiten: $mitHours");
        $this->comment('Quelle: eigene Seite (First-Party). Stand prüfen/revalidieren vor Veröffentlichung.');
        return self::SUCCESS;
    }

    private function parse(string $html, string $slug): ?array
    {
        $clean = fn ($s) => trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $s), ENT_QUOTES)));

        // Info-Block isolieren
        if (! preg_match('~class="section-z-info.*?</section>~s', $html, $mm)) return null;
        $block = $mm[0];
        $text = $clean($block);

        // Name = erste <h2> im Info-Block (z. B. "Kreis … Straßenverkehrsbehörde in …")
        $name = null;
        if (preg_match('~<h2[^>]*>(.*?)</h2>~s', $block, $h)) $name = $clean($h[1]);
        if (! $name && preg_match('~([\p{L}].*?)\s+place\s+Created~u', $text, $p)) $name = trim($p[1]);

        // Anschrift: nach "place Created with Sketch." bis "local_phone"
        $strasse = $plz = $ort = null;
        if (preg_match('~place(?:\s+Created with Sketch\.)?\s*(.*?)\s+(?:local_phone|mail|Öffnungszeiten)~', $text, $a)) {
            $addr = trim($a[1]);
            if (preg_match('~^(.*?),\s*(\d{5})\s+(.+)$~', $addr, $x)) {
                [$strasse, $plz, $ort] = [trim($x[1]), $x[2], trim($x[3])];
            }
        }

        // Telefon/Fax: Region nach "local_phone" holen, an Nummern-Grenzen (" 0…") trennen
        $telefon = $fax = null;
        if (preg_match('~local_phone(?:\s+Created with Sketch\.)?\s*([0-9 /()+\-]{5,}?)\s*(?:mail|Öffnungszeiten|$)~u', $text, $t)) {
            $nummern = preg_split('~\s+(?=0\d)~', trim($t[1]), 2);
            $telefon = trim($nummern[0]) ?: null;
            $fax = isset($nummern[1]) ? trim($nummern[1]) : null;
        }
        // E-Mail
        $email = null;
        if (preg_match('~mail(?:\s+Created with Sketch\.)?\s*([\w.\-]+@[\w.\-]+\.\w+)~', $text, $e)) $email = $e[1];

        // Öffnungszeiten zwischen "Öffnungszeiten" und "Erläuterung"/Ende
        $hours = null;
        if (preg_match('~Öffnungszeiten\s*(.*?)(?:Erläuterung|Bedingt|$)~s', $text, $o)) {
            $hours = $this->parseHours($o[1]);
        }

        // Termin-URL (id="termin")
        [$terminUrl, $terminSys] = $this->linkNach($html, 'termin');
        // Funnel-/Affiliate-URL (id="reservieren") + Kürzel aus symbol=
        [$funnelUrl] = $this->linkNach($html, 'reservieren');
        $funnelUrl = $funnelUrl ? html_entity_decode($funnelUrl, ENT_QUOTES) : null;
        $kuerzel = null;
        if ($funnelUrl && preg_match('~symbol=([A-ZÄÖÜ]{1,3})~i', $funnelUrl, $s)) $kuerzel = mb_strtoupper($s[1]);

        // Zulassungsbezirk-Überschrift (mb-sicher kürzen, nicht mitten im Zeichen)
        $bezirk = null;
        if (preg_match('~Zulassungsbezirk\s+([^<]{2,120})~u', strip_tags($html), $z)) {
            $bezirk = mb_substr($clean($z[1]), 0, 80);
        }

        // alle String-Felder gegen ungültiges UTF-8 absichern
        $u8 = fn ($s) => $s === null ? null : mb_convert_encoding((string) $s, 'UTF-8', 'UTF-8');

        return array_map(fn ($v) => is_string($v) ? $u8($v) : $v, [
            'url'              => 'https://www.wunschkennzeichen-reservieren.de/zulassungsstelle/'.$slug.'/',
            'ort_slug'         => $slug,
            'name'             => $name ? Str::limit($name, 250, '') : null,
            'strasse'          => $strasse,
            'plz'              => $plz,
            'ort'              => $ort,
            'telefon'          => $telefon,
            'fax'              => $fax,
            'email'            => $email,
            'oeffnungszeiten'  => $hours,
            'termin_url'       => $terminUrl ? html_entity_decode($terminUrl) : null,
            'termin_system'    => $terminSys,
            'funnel_url'       => $funnelUrl ? html_entity_decode($funnelUrl) : null,
            'kuerzel'          => $kuerzel,
            'zulassungsbezirk' => $bezirk,
            'fetched_at'       => now(),
        ]);
    }

    /** Erste http(s)-URL nach dem Anker id="$id"; erkennt das Terminsystem. */
    private function linkNach(string $html, string $id): array
    {
        if (! preg_match('~id="'.$id.'".*?(https?://[^"\s\)]+)~s', $html, $m)) return [null, null];
        $url = $m[1];
        $sys = null;
        foreach (['frontdesksuite' => 'frontdesksuite', 'tevis' => 'tevis', 'etermin' => 'eTermin', 'terminland' => 'Terminland', 'netappoint' => 'netAppoint', 'tnv' => 'TNV', 'qmatic' => 'Qmatic'] as $needle => $label) {
            if (stripos($url, $needle) !== false) { $sys = $label; break; }
        }
        return [$url, $sys];
    }

    /** "Mo. 07:30 - 12:00 Uhr Di. … und 13:30 - 15:30 Uhr" → [{day,label,zeiten[]}]. */
    private function parseHours(string $s): ?array
    {
        $s = preg_replace('/\s+/', ' ', $s);
        // an Tageskürzeln splitten
        $parts = preg_split('/(?=\b(?:Mo|Di|Mi|Do|Fr|Sa|So)\.)/u', $s, -1, PREG_SPLIT_NO_EMPTY);
        $out = [];
        foreach ($parts as $part) {
            if (! preg_match('/^(Mo|Di|Mi|Do|Fr|Sa|So)\.\s*(.+)/u', trim($part), $m)) continue;
            preg_match_all('/(\d{1,2}[:.]\d{2})\s*-\s*(\d{1,2}[:.]\d{2})/', $m[2], $z, PREG_SET_ORDER);
            if (! $z) continue;
            $zeiten = array_map(fn ($t) => ['opens' => str_replace('.', ':', $t[1]), 'closes' => str_replace('.', ':', $t[2])], $z);
            $out[] = ['day' => self::TAGE[$m[1]], 'label' => $m[1], 'zeiten' => $zeiten];
        }
        return $out ?: null;
    }
}
