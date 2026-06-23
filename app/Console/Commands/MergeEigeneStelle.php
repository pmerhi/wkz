<?php

namespace App\Console\Commands;

use App\Models\EigeneStelle;
use App\Models\Zulassungsstelle;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Übernimmt die First-Party-Daten der eigenen Seite (`eigene_stelle`) in unsere
 * `zulassungsstellen` — Match über **PLZ + Straße**. Füllt vor allem unsere Lücken:
 * Termin-URL und (2024er-)Öffnungszeiten, ergänzt Telefon/E-Mail. Herkunft in `quelle`.
 * Standardmäßig werden nur fehlende Werte ergänzt; `--frisch` überschreibt
 * Öffnungszeiten/Termin-URL mit dem aktuelleren First-Party-Stand.
 */
class MergeEigeneStelle extends Command
{
    protected $signature = 'merge:eigene {--dry} {--frisch : Öffnungszeiten & Termin-URL mit First-Party-Stand überschreiben}';

    protected $description = 'Übernimmt eigene-Seite-Daten (Termin-URL, Öffnungszeiten, Kontakt) in unsere Stellen (Match PLZ+Straße).';

    public function handle(): int
    {
        $dry = $this->option('dry');
        $frisch = $this->option('frisch');

        // Unsere Stellen nach PLZ+Straßenschlüssel indexieren (eindeutige Schlüssel).
        $index = [];
        $ambig = [];
        foreach (Zulassungsstelle::whereNotNull('plz')->whereNotNull('strasse')->get() as $s) {
            $k = $s->plz.'|'.$this->streetKey($s->strasse);
            if (isset($index[$k])) { $ambig[$k] = true; }
            $index[$k] = $s;
        }

        $matched = 0; $termin = 0; $hours = 0; $kontakt = 0; $keinMatch = 0; $mehrdeutig = 0;
        foreach (EigeneStelle::whereNotNull('plz')->whereNotNull('strasse')->get() as $e) {
            $k = $e->plz.'|'.$this->streetKey($e->strasse);
            if (isset($ambig[$k])) { $mehrdeutig++; continue; }     // mehrdeutig → Sicherheit vor Falschzuordnung
            $s = $index[$k] ?? null;
            if (! $s) { $keinMatch++; continue; }
            $matched++;

            $u = [];
            // Termin-URL: unsere größte Lücke
            if ($e->termin_url && ($frisch || empty($s->termin_url))) {
                $u['termin_url'] = $e->termin_url;
                $termin++;
            }
            // Öffnungszeiten: First-Party (2024) bevorzugt, ins Frontend-Format wandeln
            $ownStructured = is_array($s->oeffnungszeiten) && ! isset($s->oeffnungszeiten['raw']) && $s->oeffnungszeiten;
            if ($e->oeffnungszeiten && ($frisch || ! $ownStructured)) {
                $flat = $this->flachOeffnungszeiten($e->oeffnungszeiten);
                if ($flat) { $u['oeffnungszeiten'] = $flat; $hours++; }
            }
            // Kontakt nur ergänzen
            foreach (['telefon', 'email'] as $f) {
                if (empty($s->$f) && ! empty($e->$f)) { $u[$f] = $e->$f; }
            }
            if (array_intersect_key($u, ['telefon' => 1, 'email' => 1])) $kontakt++;

            if ($u) {
                $u['quelle'] = $this->quelleVermerk($s->quelle);
                if (! $dry) $s->update($u);
            }
        }

        $this->info(($dry ? '[DRY] ' : '')."Gematcht: $matched · Termin-URL gesetzt: $termin · Öffnungszeiten gesetzt: $hours · Kontakt ergänzt: $kontakt");
        $this->line("Kein Match (PLZ+Straße): $keinMatch · mehrdeutige Schlüssel übersprungen: $mehrdeutig");
        $this->comment('Quelle: eigene Seite (First-Party, Stand 2024). Vor Veröffentlichung revalidieren.');
        return self::SUCCESS;
    }

    /** [{day,label,zeiten:[{opens,closes}]}] → [{day,label,opens,closes}] (Frontend-Format). */
    private function flachOeffnungszeiten($raw): ?array
    {
        if (! is_array($raw)) return null;
        $out = [];
        foreach ($raw as $tag) {
            if (! is_array($tag) || empty($tag['zeiten'])) continue;
            foreach ($tag['zeiten'] as $z) {
                if (! isset($z['opens'], $z['closes'])) continue;
                $out[] = ['day' => $tag['day'] ?? null, 'label' => $tag['label'] ?? null, 'opens' => $z['opens'], 'closes' => $z['closes']];
            }
        }
        return $out ?: null;
    }

    private function quelleVermerk(?string $q): string
    {
        $note = 'eigene Seite (First-Party, Stand 2024)';
        return str_contains((string) $q, $note) ? (string) $q : trim(($q ?: '').' · '.$note, ' ·');
    }

    /** Straßenname + erste Hausnummer, normalisiert (wie in Consolidate/Dedupe). */
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
