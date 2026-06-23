<?php

namespace App\Console\Commands;

use App\Models\Gemeinde;
use App\Models\KonsolidierteStelle;
use App\Models\Zulassungsstelle;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Übernimmt den konsolidierten Datensatz in die Produkt-Tabelle `zulassungsstellen`
 * (anwaltlich freigegeben): bestehende anreichern, fehlende neu anlegen — mit AGS,
 * Bundesland und Öffnungszeiten.
 */
class ImportKonsolidiert extends Command
{
    protected $signature = 'import:konsolidiert {--dry}';

    protected $description = 'Übernimmt konsolidierte Stellen in die Produkt-Tabelle (Update + Neuanlage).';

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
        $dry = $this->option('dry');
        $blByGemeinde = Gemeinde::pluck('bundesland_id', 'id');

        // bestehende Stellen nach Identität indexieren
        $existing = [];
        foreach (Zulassungsstelle::all() as $s) {
            if ($s->plz && $s->strasse) {
                $existing[$s->plz.'|'.$this->streetKey($s->strasse)] = $s;
            }
        }

        $created = 0; $updated = 0;
        foreach (KonsolidierteStelle::all() as $k) {
            $hours = $this->normHours($k->oeffnungszeiten);
            $bl = $k->gemeinde_id ? ($blByGemeinde[$k->gemeinde_id] ?? null) : null;
            $id = $k->plz.'|'.$this->streetKey((string) $k->strasse);
            $s = $existing[$id] ?? null;

            if ($s) {
                $u = [];
                foreach (['telefon', 'email', 'website', 'strasse', 'ort'] as $f) {
                    if (empty($s->$f) && ! empty($k->$f)) $u[$f] = $k->$f;
                }
                if ($hours && (! is_array($s->oeffnungszeiten) || isset($s->oeffnungszeiten['raw']))) $u['oeffnungszeiten'] = $hours;
                if (empty($s->bundesland_id) && $bl) $u['bundesland_id'] = $bl;
                if (empty($s->gemeinde_id) && $k->gemeinde_id) $u['gemeinde_id'] = $k->gemeinde_id;
                if (empty($s->kreis_id) && $k->kreis_id) $u['kreis_id'] = $k->kreis_id;
                if ($u) { $u['quelle'] = trim(($s->quelle ?: '').' · Konsolidat (freigegeben)'); $updated++; if (! $dry) $s->update($u); }
            } else {
                $created++;
                if (! $dry) {
                    Zulassungsstelle::create([
                        'name'             => $k->name ?: ('Zulassungsstelle '.$k->ort),
                        'slug'             => $this->uniqueSlug($k->name ?: $k->ort, $k->plz),
                        'strasse'          => $k->strasse,
                        'plz'              => $k->plz,
                        'ort'              => $k->ort,
                        'telefon'          => $k->telefon,
                        'email'            => $k->email,
                        'website'          => $k->website,
                        'oeffnungszeiten'  => $hours,
                        'bundesland_id'    => $bl,
                        'gemeinde_id'      => $k->gemeinde_id,
                        'kreis_id'         => $k->kreis_id,
                        'quelle'           => 'Wettbewerber-Konsolidat (anwaltlich freigegeben)',
                        'last_imported_at' => now(),
                    ]);
                }
            }
        }

        $this->info(($dry ? '[DRY] ' : '')."Neu angelegt: $created · aktualisiert: $updated · Stellen gesamt: ".Zulassungsstelle::count());
        return self::SUCCESS;
    }

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

    private function uniqueSlug(?string $name, ?string $plz): string
    {
        $base = Str::slug($name ?: 'zulassungsstelle') ?: 'stelle';
        $slug = $base; $i = 2;
        while (Zulassungsstelle::where('slug', $slug)->exists()) {
            $slug = $base.'-'.($i === 2 && $plz ? $plz : $i);
            $i++;
        }
        return $slug;
    }
}
