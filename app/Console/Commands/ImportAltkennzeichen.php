<?php

namespace App\Console\Commands;

use App\Models\ExtraktKuerzel;
use App\Models\KennzeichenKuerzel;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Markiert die im Rahmen der Kennzeichenliberalisierung wieder eingeführten
 * Altkennzeichen (Quelle: Wikipedia „Liste der deutschen Kfz-Kennzeichen, die nicht
 * mehr ausgegeben werden", kursiv = wieder eingeführt) und korrigiert/ergänzt deren
 * Bedeutung aus dem anwaltlich freigegebenen Wettbewerber-Konsolidat.
 *
 *  - fehlende Codes werden neu angelegt
 *  - `ist_altkennzeichen` wird gesetzt, `historische_stadt` = ursprüngliche Bedeutung
 *  - `bedeutung` (aktueller Zulassungsbezirk) wird aus dem Konsolidat ergänzt/korrigiert
 */
class ImportAltkennzeichen extends Command
{
    protected $signature = 'import:altkennzeichen {--dry : Nur zeigen, was passieren würde}';

    protected $description = 'Markiert wieder eingeführte Altkennzeichen und korrigiert deren Bedeutung.';

    public function handle(): int
    {
        $dry = $this->option('dry');
        $path = database_path('data/altkennzeichen.json');
        if (! is_file($path)) {
            $this->error("Datenquelle fehlt: $path");
            return self::FAILURE;
        }
        $alt = json_decode(file_get_contents($path), true);
        if (! is_array($alt)) {
            $this->error('altkennzeichen.json konnte nicht gelesen werden.');
            return self::FAILURE;
        }

        // Saubere aktuelle Bedeutung (Kreis, Bundesland) je Code aus dem Konsolidat.
        // kennzeichenking.de hat die konsistenteste „Kreis, Bundesland"-Form → bevorzugt.
        $kreisByCode = [];
        foreach (ExtraktKuerzel::query()
            ->join('wettbewerber', 'wettbewerber.id', '=', 'extrakt_kuerzel.wettbewerber_id')
            ->whereNotNull('extrakt_kuerzel.bedeutung')
            ->orderByRaw("CASE WHEN wettbewerber.domain = 'kennzeichenking.de' THEN 0 ELSE 1 END")
            ->get(['extrakt_kuerzel.code', 'extrakt_kuerzel.bedeutung']) as $r) {
            if (! isset($kreisByCode[$r->code])) {
                $kreisByCode[$r->code] = trim($r->bedeutung);
            }
        }

        $created = 0; $flagged = 0; $bedeutungFixed = 0;
        foreach ($alt as $row) {
            $code = $row['code'] ?? null;
            $stadt = $row['stadt'] ?? null;
            if (! $code) continue;

            $k = KennzeichenKuerzel::firstOrNew(['code' => $code]);
            $isNew = ! $k->exists;
            $aktuelleBedeutung = $kreisByCode[$code] ?? null;

            $u = [
                'ist_altkennzeichen' => true,
                'historische_stadt'  => $stadt,
            ];
            if (! $k->slug) {
                $u['slug'] = $this->uniqueSlug($code);
            }
            // Bedeutung = aktueller Zulassungsbezirk; nur (über)schreiben, wenn das
            // Konsolidat einen sauberen Wert liefert und er fehlt oder abweicht.
            if ($aktuelleBedeutung && $k->bedeutung !== $aktuelleBedeutung) {
                $u['bedeutung'] = $aktuelleBedeutung;
                $u['bedeutung_quelle'] = 'Wettbewerber-Konsolidat (freigegeben)';
                $bedeutungFixed++;
            }

            if ($isNew) {
                $created++;
                $this->line("  + neu: $code — $stadt → ".($aktuelleBedeutung ?: '?'));
            }
            $flagged++;

            if (! $dry) {
                $k->fill($u)->save();
            }
        }

        // Sicherstellen, dass alle übrigen Codes explizit NICHT als Altkennzeichen gelten.
        $altCodes = array_column($alt, 'code');
        if (! $dry) {
            KennzeichenKuerzel::whereNotIn('code', $altCodes)
                ->where('ist_altkennzeichen', true)
                ->update(['ist_altkennzeichen' => false]);
        }

        $this->info(($dry ? '[DRY] ' : '')."Altkennzeichen markiert: $flagged · neu angelegt: $created · Bedeutung korrigiert/ergänzt: $bedeutungFixed");
        $this->comment('Quelle Markierung: Wikipedia (kursiv = wieder eingeführt) · Bedeutung: Wettbewerber-Konsolidat (anwaltlich freigegeben).');
        return self::SUCCESS;
    }

    private function uniqueSlug(string $code): string
    {
        $base = \App\Support\Slug::de($code) ?: Str::lower($code);
        $slug = $base; $i = 2;
        while (KennzeichenKuerzel::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i; $i++;
        }
        return $slug;
    }
}
