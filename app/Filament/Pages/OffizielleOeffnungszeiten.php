<?php

namespace App\Filament\Pages;

use App\Models\Zulassungsstelle;
use App\Support\Oeffnungszeiten;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

/**
 * Review der von offiziellen Behördenseiten extrahierten Öffnungszeiten:
 * offiziell vs. aktuell live, je Stelle einzeln übernehmbar.
 */
class OffizielleOeffnungszeiten extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationGroup = 'Daten';
    protected static ?string $navigationLabel = 'Offizielle Öffnungszeiten';
    protected static ?string $title = 'Offizielle Öffnungszeiten – Review';

    protected static string $view = 'filament.pages.offizielle-oeffnungszeiten';

    /** Eine offizielle Variante in die Live-Daten übernehmen. */
    public function uebernehmen(int $stelleId): void
    {
        $row = DB::table('offizielle_oeffnungszeiten')->where('zulassungsstelle_id', $stelleId)->first();
        $stelle = Zulassungsstelle::find($stelleId);
        if (! $row || ! $stelle) {
            Notification::make()->title('Nicht gefunden')->danger()->send();
            return;
        }
        $zeiten = Oeffnungszeiten::kanonisieren(json_decode($row->oeffnungszeiten, true) ?: []);
        if (! $zeiten) {
            Notification::make()->title('Keine übernehmbaren Zeiten')->warning()->send();
            return;
        }
        $host = parse_url($row->quelle_url, PHP_URL_HOST) ?: 'offiziell';
        $stelle->oeffnungszeiten = $zeiten;
        $stelle->quelle = trim(($stelle->quelle ?: '').' · Öffnungszeiten: offiziell ('.$host.')');
        $stelle->oeffnungszeiten_geaendert = false;
        $stelle->save();
        DB::table('offizielle_oeffnungszeiten')->where('id', $row->id)->update(['uebernommen' => true, 'updated_at' => now()]);

        Notification::make()->title('Übernommen')->body($stelle->name)->success()->send();
    }

    protected function getViewData(): array
    {
        $rows = DB::table('offizielle_oeffnungszeiten')->get()
            ->sortBy(fn ($r) => [$r->uebernommen ? 1 : 0, $r->status === 'ok' ? 0 : 1])
            ->values();

        $eintraege = [];
        $zaehler = ['ok' => 0, 'uebernommen' => 0, 'problem' => 0];
        foreach ($rows as $r) {
            $stelle = Zulassungsstelle::find($r->zulassungsstelle_id);
            if (! $stelle) {
                continue;
            }
            $offiziell = json_decode($r->oeffnungszeiten, true) ?: [];
            $live = is_array($stelle->oeffnungszeiten) ? $stelle->oeffnungszeiten : [];

            if ($r->uebernommen) {
                $zaehler['uebernommen']++;
            } elseif ($r->status === 'ok') {
                $zaehler['ok']++;
            } else {
                $zaehler['problem']++;
            }

            $eintraege[] = [
                'stelle'     => $stelle,
                'status'     => $r->status,
                'hinweis'    => $r->hinweis,
                'quelle_url' => $r->quelle_url,
                'uebernommen' => (bool) $r->uebernommen,
                'abweichung' => $this->fp($offiziell) !== $this->fp($live),
                'woche_off'  => Oeffnungszeiten::woche($offiziell),
                'woche_live' => Oeffnungszeiten::woche($live),
                'hat_off'    => (bool) $offiziell,
            ];
        }

        return ['eintraege' => $eintraege, 'zaehler' => $zaehler];
    }

    private function fp(array $oz): string
    {
        $s = [];
        foreach (Oeffnungszeiten::kanonisieren($oz) as $e) {
            $s[] = $e['day'].$e['opens'].$e['closes'];
        }

        return implode('|', $s);
    }
}
