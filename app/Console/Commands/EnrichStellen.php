<?php

namespace App\Console\Commands;

use App\Models\KonsolidierteStelle;
use App\Models\Zulassungsstelle;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Gleicht unsere eigenen Zulassungsstellen mit dem konsolidierten Wettbewerber-
 * Datensatz ab: übernimmt Öffnungszeiten und ergänzt fehlende Kontaktfelder.
 * Herkunft wird in `quelle` vermerkt. Produktnutzung erst nach anwaltlicher Freigabe.
 */
class EnrichStellen extends Command
{
    protected $signature = 'enrich:stellen {--dry : Nur zeigen, was passieren würde}';

    protected $description = 'Reichert eigene Stellen aus dem Wettbewerber-Konsolidat an (Öffnungszeiten + Kontakt).';

    private const DAYS = [
        'monday' => ['Monday', 'Montag'], 'montag' => ['Monday', 'Montag'],
        'tuesday' => ['Tuesday', 'Dienstag'], 'dienstag' => ['Tuesday', 'Dienstag'],
        'wednesday' => ['Wednesday', 'Mittwoch'], 'mittwoch' => ['Wednesday', 'Mittwoch'],
        'thursday' => ['Thursday', 'Donnerstag'], 'donnerstag' => ['Thursday', 'Donnerstag'],
        'friday' => ['Friday', 'Freitag'], 'freitag' => ['Friday', 'Freitag'],
        'saturday' => ['Saturday', 'Samstag'], 'samstag' => ['Saturday', 'Samstag'],
        'sunday' => ['Sunday', 'Sonntag'], 'sonntag' => ['Sunday', 'Sonntag'],
    ];

    public function handle(): int
    {
        // Konsolidat indexieren: Identität (plz|strKey) + Fallback PLZ (nur eindeutige)
        $byId = [];
        $byPlz = [];
        foreach (KonsolidierteStelle::all() as $k) {
            $id = $k->plz.'|'.$this->streetKey((string) $k->strasse);
            $byId[$id] = $k;
            $byPlz[$k->plz][] = $k;
        }

        $matched = 0; $hoursTaken = 0; $filled = 0; $dry = $this->option('dry');

        foreach (Zulassungsstelle::all() as $s) {
            $k = null;
            if ($s->strasse && $s->plz) {
                $k = $byId[$s->plz.'|'.$this->streetKey($s->strasse)] ?? null;
            }
            if (! $k && $s->plz && count($byPlz[$s->plz] ?? []) === 1) {
                $k = $byPlz[$s->plz][0];   // eindeutige PLZ als Fallback
            }
            if (! $k) continue;
            $matched++;

            $update = [];
            $hours = $this->normHours($k->oeffnungszeiten);
            $ownStructured = is_array($s->oeffnungszeiten) && ! isset($s->oeffnungszeiten['raw']) && $s->oeffnungszeiten;
            if ($hours && ! $ownStructured) {
                $update['oeffnungszeiten'] = $hours;
                $hoursTaken++;
            }
            foreach (['telefon', 'email', 'website', 'strasse'] as $f) {
                if (empty($s->$f) && ! empty($k->$f)) $update[$f] = $k->$f;
            }
            if ($update) {
                $filled++;
                $update['quelle'] = trim(($s->quelle ?: '').' · Anreicherung: Wettbewerber-Konsolidat (intern)');
                if (! $dry) $s->update($update);
            }
        }

        $this->info(($dry ? '[DRY] ' : '')."Gematcht: $matched · Öffnungszeiten übernommen: $hoursTaken · Stellen ergänzt: $filled");
        $this->comment('Herkunft in `quelle` vermerkt. Veröffentlichung erst nach anwaltlicher Freigabe.');
        return self::SUCCESS;
    }

    /** Öffnungszeiten vereinheitlichen → [{day(EN), label(DE), opens, closes}]. */
    private function normHours($raw): ?array
    {
        if (! is_array($raw)) return null;
        $out = [];
        foreach ($raw as $z) {
            if (! is_array($z) || ! isset($z['opens'], $z['closes'])) continue;
            $d = strtolower(basename((string) ($z['day'] ?? '')));
            [$en, $de] = self::DAYS[$d] ?? [null, ($z['day'] ?? null)];
            $out[] = array_filter(['day' => $en, 'label' => $de, 'opens' => $z['opens'], 'closes' => $z['closes']]);
        }
        return $out ?: null;
    }

    private function streetKey(string $s): string
    {
        $s = Str::lower($s);
        $s = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $s);
        $s = preg_replace('/stra(ss|ß)e|str\.?/u', 'str', $s);
        $num = preg_match('/\d+/', $s, $m) ? $m[0] : '';
        $name = preg_replace('/[^a-z0-9]+/u', '', preg_replace('/\d.*$/u', '', $s));
        return $name.$num;
    }
}
