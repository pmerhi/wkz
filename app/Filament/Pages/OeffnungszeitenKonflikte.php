<?php

namespace App\Filament\Pages;

use App\Models\Zulassungsstelle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Werkzeug zur Auflösung widersprüchlicher Öffnungszeiten je Zulassungsstelle.
 * Zeigt pro Stelle die Kandidaten aus wunschkennzeichen-reservieren.de (eigene_stelle)
 * und kennzeichenking.de (extrakt_zulassungsstelle) nebeneinander; der Nutzer wählt eine
 * Variante, die als bereinigte Öffnungszeiten gespeichert wird.
 */
class OeffnungszeitenKonflikte extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Daten';
    protected static ?string $navigationLabel = 'Öffnungszeiten-Konflikte';
    protected static ?string $title = 'Öffnungszeiten-Konflikte auflösen';

    protected static string $view = 'filament.pages.oeffnungszeiten-konflikte';

    private const TAGE = [
        'Monday' => 'Mo', 'Tuesday' => 'Di', 'Wednesday' => 'Mi', 'Thursday' => 'Do',
        'Friday' => 'Fr', 'Saturday' => 'Sa', 'Sunday' => 'So',
    ];

    private const KURZ = [
        'mo' => 'Monday', 'di' => 'Tuesday', 'mi' => 'Wednesday', 'do' => 'Thursday',
        'fr' => 'Friday', 'sa' => 'Saturday', 'so' => 'Sunday',
    ];

    /** Eine gewählte Variante als Öffnungszeiten der Stelle speichern. */
    public function uebernehmen(int $stelleId, array $flat, string $quelle): void
    {
        $stelle = Zulassungsstelle::find($stelleId);
        if (! $stelle) {
            Notification::make()->title('Stelle nicht gefunden')->danger()->send();
            return;
        }

        // Kanonisieren + exakte Dubletten entfernen, nach Wochentag sortieren.
        $clean = $this->kanonisieren($flat);
        $stelle->oeffnungszeiten = $clean;
        $stelle->quelle = trim(($stelle->quelle ?: '').' · Öffnungszeiten geprüft: '.$quelle);
        $stelle->save();

        Notification::make()
            ->title('Übernommen')
            ->body($stelle->name.' → '.$quelle)
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        $stellen = [];
        foreach (Zulassungsstelle::whereNotNull('oeffnungszeiten')->orderBy('name')->get() as $s) {
            if (! $this->hatKonflikt($s->oeffnungszeiten)) {
                continue;
            }
            $stellen[] = [
                'stelle'     => $s,
                'aktuell'    => $this->woche($this->normalisieren($s->oeffnungszeiten)),
                'kandidaten' => $this->kandidaten($s),
            ];
        }

        return ['stellen' => $stellen, 'anzahl' => count($stellen)];
    }

    /** Kandidaten-Varianten aus beiden Quellen aufbauen. */
    private function kandidaten(Zulassungsstelle $s): array
    {
        $out = [];

        // --- Quelle 1: wunschkennzeichen-reservieren.de (eigene_stelle) ---
        $eigene = $this->findeEigeneStelle($s);
        if ($eigene && $eigene->oeffnungszeiten) {
            $sets = $this->inSets($this->normalisieren(json_decode($eigene->oeffnungszeiten, true)));
            foreach ($sets as $i => $flat) {
                $out[] = [
                    'quelle' => 'wunschkennzeichen-reservieren.de',
                    'badge'  => count($sets) > 1 ? 'Variante '.($i + 1).' von '.count($sets) : null,
                    'flat'   => $flat,
                    'woche'  => $this->woche($flat),
                ];
            }
        }

        // --- Quelle 2: kennzeichenking.de (extrakt_zulassungsstelle) ---
        $kk = $this->findeKennzeichenking($s);
        if ($kk && $kk->oeffnungszeiten) {
            $sets = $this->inSets($this->normalisieren(json_decode($kk->oeffnungszeiten, true)));
            foreach ($sets as $i => $flat) {
                $out[] = [
                    'quelle' => 'kennzeichenking.de',
                    'badge'  => count($sets) > 1 ? 'Variante '.($i + 1).' von '.count($sets) : null,
                    'flat'   => $flat,
                    'woche'  => $this->woche($flat),
                ];
            }
        }

        return $out;
    }

    /** eigene_stelle über PLZ + Straßenschlüssel, sonst Ort/Name finden. */
    private function findeEigeneStelle(Zulassungsstelle $s): ?object
    {
        $q = DB::table('eigene_stelle')->whereNotNull('oeffnungszeiten');
        if ($s->plz && $s->strasse) {
            $treffer = (clone $q)->where('plz', $s->plz)
                ->get(['name', 'strasse', 'oeffnungszeiten'])
                ->first(fn ($e) => $this->streetKey((string) $e->strasse) === $this->streetKey((string) $s->strasse));
            if ($treffer) {
                return $treffer;
            }
        }

        return (clone $q)
            ->where(fn ($w) => $w->where('ort_slug', $s->slug)->orWhere('ort', $s->ort))
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', ['%'.$s->ort.'%'])
            ->first(['name', 'strasse', 'oeffnungszeiten']);
    }

    /** kennzeichenking.de über Gemeinde, sonst Kreis (mit Namensnähe) finden. */
    private function findeKennzeichenking(Zulassungsstelle $s): ?object
    {
        $base = DB::table('extrakt_zulassungsstelle')
            ->join('wettbewerber', 'wettbewerber.id', '=', 'extrakt_zulassungsstelle.wettbewerber_id')
            ->where('wettbewerber.domain', 'kennzeichenking.de')
            ->whereNotNull('extrakt_zulassungsstelle.oeffnungszeiten')
            ->where('extrakt_zulassungsstelle.oeffnungszeiten', '!=', '[]');

        foreach ([['gemeinde_id', $s->gemeinde_id], ['kreis_id', $s->kreis_id]] as [$spalte, $wert]) {
            if (! $wert) {
                continue;
            }
            $treffer = (clone $base)
                ->where('extrakt_zulassungsstelle.'.$spalte, $wert)
                ->orderByRaw('CASE WHEN extrakt_zulassungsstelle.name LIKE ? THEN 0 ELSE 1 END', ['%'.$s->ort.'%'])
                ->first(['extrakt_zulassungsstelle.name', 'extrakt_zulassungsstelle.oeffnungszeiten']);
            if ($treffer) {
                return $treffer;
            }
        }

        return null;
    }

    /** Rohformat (eigene_stelle ODER extrakt/Stelle) → flache, kanonische Liste. */
    private function normalisieren($raw): array
    {
        if (! is_array($raw) || isset($raw['raw'])) {
            return [];
        }
        $out = [];
        foreach ($raw as $z) {
            if (! is_array($z)) {
                continue;
            }
            $day = $this->tagKey($z);
            if (! $day) {
                continue;
            }
            // eigene_stelle: verschachtelte "zeiten"; sonst direkt opens/closes.
            $intervalle = isset($z['zeiten']) && is_array($z['zeiten']) ? $z['zeiten'] : [$z];
            foreach ($intervalle as $iv) {
                if (! isset($iv['opens'], $iv['closes'])) {
                    continue;
                }
                $out[] = [
                    'day'    => $day,
                    'label'  => self::TAGE[$day],
                    'opens'  => substr((string) $iv['opens'], 0, 5),
                    'closes' => substr((string) $iv['closes'], 0, 5),
                ];
            }
        }

        return $out;
    }

    /** Flache Liste in sequentielle Wochen-Sets aufteilen (Tag wiederholt = neues Set). */
    private function inSets(array $flat): array
    {
        $sets = [];
        $aktuell = [];
        $gesehen = [];
        foreach ($flat as $e) {
            $tagKey = $e['day'];
            if (isset($gesehen[$tagKey]) && ! $this->istSplit($aktuell, $e)) {
                $sets[] = $aktuell;
                $aktuell = [];
                $gesehen = [];
            }
            $aktuell[] = $e;
            $gesehen[$tagKey] = true;
        }
        if ($aktuell) {
            $sets[] = $aktuell;
        }

        return array_map(fn ($s) => $this->kanonisieren($s), $sets) ?: [];
    }

    /** Gleicher Tag, aber nicht-überlappendes Intervall = echter Split (kein neues Set). */
    private function istSplit(array $set, array $neu): bool
    {
        foreach ($set as $e) {
            if ($e['day'] !== $neu['day']) {
                continue;
            }
            // überlappt → Konflikt (neues Set); disjunkt → Split (bleibt im Set)
            if ($e['opens'] < $neu['closes'] && $neu['opens'] < $e['closes']) {
                return false;
            }
        }

        return true;
    }

    /** Exakte Dubletten entfernen, nach Wochentag + Startzeit sortieren. */
    private function kanonisieren(array $flat): array
    {
        $order = array_flip(array_keys(self::TAGE));
        $seen = [];
        $out = [];
        foreach ($flat as $e) {
            $key = $e['day'].'|'.$e['opens'].'|'.$e['closes'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $e;
        }
        usort($out, fn ($a, $b) => [$order[$a['day']] ?? 9, $a['opens']] <=> [$order[$b['day']] ?? 9, $b['opens']]);

        return $out;
    }

    /** Flache Liste → ['Mo' => ['08:00–12:00', …], …] für die Anzeige (alle 7 Tage). */
    private function woche(array $flat): array
    {
        $woche = array_fill_keys(array_values(self::TAGE), []);
        foreach ($flat as $e) {
            $woche[$e['label']][] = $e['opens'].'–'.$e['closes'];
        }

        return $woche;
    }

    private function hatKonflikt($raw): bool
    {
        $perDay = [];
        foreach (is_array($raw) ? $raw : [] as $e) {
            if (! is_array($e) || ! isset($e['opens'], $e['closes'], $e['day'])) {
                continue;
            }
            $perDay[$e['day']][] = [$this->toMin($e['opens']), $this->toMin($e['closes'])];
        }
        foreach ($perDay as $iv) {
            $n = count($iv);
            for ($i = 0; $i < $n; $i++) {
                for ($j = $i + 1; $j < $n; $j++) {
                    if ($iv[$i][0] < $iv[$j][1] && $iv[$j][0] < $iv[$i][1]) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function tagKey(array $z): ?string
    {
        $d = (string) ($z['day'] ?? '');
        if (isset(self::TAGE[$d])) {
            return $d;
        }
        $l = mb_strtolower(substr((string) ($z['label'] ?? $d), 0, 2));

        return self::KURZ[$l] ?? null;
    }

    private function toMin($t): int
    {
        return preg_match('/(\d{1,2})[:.](\d{2})/', (string) $t, $m) ? (int) $m[1] * 60 + (int) $m[2] : 0;
    }

    private function streetKey(string $s): string
    {
        $s = Str::lower($s);
        $s = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $s);
        $num = preg_match('/\d+/', $s, $m) ? $m[0] : '';

        return preg_replace('/[^a-z]/', '', $s).$num;
    }
}
