<?php

namespace App\Console\Commands;

use App\Models\RatgeberThema;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Ergänzt die Ratgeber-Queue um Themen-Lücken aus der Wettbewerbsanalyse
 * (v. a. kennzeichenking-Blog): ganze Cluster Führerschein & MPU sowie
 * Nischen-Kennzeichen/Dokumente, die unsere/eigene Seite nicht abdeckt.
 * Herkunft je Thema in `notiz` vermerkt.
 */
class SeedRatgeberThemenGaps extends Command
{
    protected $signature = 'ratgeber:themen-gaps';

    protected $description = 'Ergänzt Wettbewerber-Themenlücken (Führerschein, MPU, Nischen) in die Ratgeber-Queue.';

    /** [kategorie, titel, focus, [keywords], intent i/k/t, vol h/m/n, funnel h/m/n, herkunft]. */
    private const GAPS = [
        // ---- Führerschein (hohes Volumen, geringe Funnel-Nähe) ----
        ['Führerschein', 'Führerschein umtauschen: Fristen für den Pflichtumtausch', 'führerschein umtauschen', ['pflichtumtausch führerschein', 'führerschein umtausch fristen', 'eu führerschein umtausch'], 'i', 'h', 'n', 'kennzeichenking'],
        ['Führerschein', 'Führerscheinklassen: Übersicht A, B, C, D & Co.', 'führerscheinklassen', ['führerschein klassen übersicht', 'welche führerscheinklasse'], 'i', 'h', 'n', 'kennzeichenking'],
        ['Führerschein', 'Führerschein verloren: Ersatz beantragen', 'führerschein verloren', ['führerschein verloren ersatz', 'führerschein neu beantragen'], 'i', 'h', 'n', 'kennzeichenking'],
        ['Führerschein', 'Was kostet der Führerschein? Kosten im Überblick', 'führerschein kosten', ['fahrschule kosten', 'führerschein klasse b kosten'], 'k', 'h', 'n', 'kennzeichenking'],
        ['Führerschein', 'EU-Führerschein & internationaler Führerschein', 'eu führerschein', ['internationaler führerschein', 'führerschein im ausland'], 'i', 'm', 'n', 'kennzeichenking'],
        ['Führerschein', 'Motorradführerschein: Klassen A, A1 und A2', 'motorradführerschein', ['führerschein a2', 'führerschein a1', 'motorrad führerschein kosten'], 'i', 'm', 'n', 'kennzeichenking'],
        ['Führerschein', 'Mofa-, Roller- & AM-Führerschein', 'mofa führerschein', ['rollerführerschein', 'führerschein am', 'mofa prüfbescheinigung'], 'i', 'm', 'n', 'kennzeichenking'],
        ['Führerschein', 'Lkw-Führerschein C/CE: Voraussetzungen & Kosten', 'lkw führerschein', ['führerschein ce', 'lkw führerschein kosten'], 'i', 'm', 'n', 'kennzeichenking'],
        ['Führerschein', 'Fahren ohne Führerschein: Strafen & Folgen', 'fahren ohne führerschein', ['fahren ohne fahrerlaubnis strafe', 'führerschein entzug'], 'i', 'm', 'n', 'kennzeichenking'],

        // ---- MPU (kommerziell stark) ----
        ['MPU', 'MPU-Vorbereitung: so bestehst du die Prüfung', 'mpu vorbereitung', ['mpu vorbereitung tipps', 'mpu beratung'], 'k', 'm', 'n', 'kennzeichenking'],
        ['MPU', 'MPU-Kosten: womit du rechnen musst', 'mpu kosten', ['mpu preis', 'mpu kosten übernahme', 'mpu haaranalyse kosten'], 'k', 'h', 'n', 'kennzeichenking'],
        ['MPU', 'MPU-Ablauf & typische Fragen (Idiotentest)', 'mpu ablauf', ['mpu fragen', 'idiotentest', 'mpu test'], 'i', 'm', 'n', 'kennzeichenking'],
        ['MPU', 'MPU umgehen & Verjährung: was wirklich geht', 'mpu verjährung', ['mpu umgehen', 'mpu fristen'], 'i', 'm', 'n', 'kennzeichenking'],

        // ---- Kennzeichen-Arten (Nischen-Lücken) ----
        ['Kennzeichen-Arten', 'Diplomatenkennzeichen & Botschafterkennzeichen', 'diplomatenkennzeichen', ['botschafterkennzeichen', 'cd kennzeichen'], 'i', 'm', 'n', 'kennzeichenking'],
        ['Kennzeichen-Arten', 'Überführungskennzeichen: Auto legal überführen', 'überführungskennzeichen', ['überführungsfahrt kennzeichen', 'auto überführen ohne zulassung'], 'i', 'm', 'm', 'kennzeichenking'],
        ['Kennzeichen-Arten', 'Behördenkennzeichen & Sonderkennzeichen', 'behördenkennzeichen', ['bundeswehr kennzeichen y', 'polizei kennzeichen', 'thw kennzeichen'], 'i', 'n', 'n', 'kennzeichenking'],
        ['Kennzeichen-Arten', 'Fahrradträger-Kennzeichen & Zusatzkennzeichen', 'fahrradträger kennzeichen', ['zusatzkennzeichen heckträger', 'kennzeichen für fahrradträger'], 'i', 'm', 'n', 'kennzeichenking'],
        ['Kennzeichen-Arten', 'Nummernschild selbst gestalten & prägen lassen', 'nummernschild prägen', ['kennzeichen selbst gestalten', 'nummernschild online bestellen'], 'k', 'm', 'm', 'kennzeichenking'],

        // ---- Zulassung / Dokumente (Lücken) ----
        ['Kfz-Zulassung', 'Fahrzeugbrief verloren – was tun?', 'fahrzeugbrief verloren', ['zulassungsbescheinigung teil 2 verloren', 'fahrzeugbrief ersatz'], 'i', 'h', 'm', 'kennzeichenking'],
        ['Kfz-Zulassung', 'Fahrzeugschein verloren – Ersatz beantragen', 'fahrzeugschein verloren', ['zulassungsbescheinigung teil 1 verloren', 'fahrzeugschein ersatz'], 'i', 'h', 'm', 'kennzeichenking'],
        ['Kfz-Zulassung', 'Fahren ohne Zulassung & ohne eVB: Strafen', 'fahren ohne zulassung', ['fahren ohne kennzeichen strafe', 'kennzeichen ohne evb', 'fahren ohne versicherung'], 'i', 'm', 'm', 'kennzeichenking'],

        // ---- Wunschkennzeichen / Halter (Lücken) ----
        ['Wunschkennzeichen', 'Wem gehört das Kennzeichen? Halter ermitteln', 'halter ermitteln kennzeichen', ['wem gehört das kennzeichen', 'kfz halterabfrage', 'kennzeichen zurückverfolgen'], 'i', 'h', 'm', 'kennzeichenking'],
        ['Wunschkennzeichen', 'Kurzes Kennzeichen bekommen: so klappt es', 'kurzes kennzeichen', ['kurze kennzeichen', 'wenig zeichen kennzeichen'], 'i', 'm', 'h', 'kennzeichenking'],

        // ---- Halter-Tipps (Lücke) ----
        ['Halter-Tipps', 'Punkte in Flensburg & Fahreignungsregister', 'punkte in flensburg', ['fahreignungsregister', 'punkte abbauen', 'punktestand abfragen'], 'i', 'h', 'n', 'kennzeichenking'],
    ];

    public function handle(): int
    {
        $vol = ['h' => 'hoch', 'm' => 'mittel', 'n' => 'niedrig'];
        $intent = ['i' => 'informational', 'k' => 'kommerziell', 't' => 'transaktional'];

        $n = 0; $neu = 0;
        foreach (self::GAPS as [$kat, $titel, $focus, $keywords, $in, $v, $f, $herkunft]) {
            $slug = \App\Support\Slug::de($focus);
            $exists = RatgeberThema::where('slug', $slug)->exists();
            $score = ['h' => 40, 'm' => 24, 'n' => 10][$f] + ['h' => 30, 'm' => 18, 'n' => 8][$v] + ['t' => 18, 'k' => 12, 'i' => 6][$in];
            RatgeberThema::updateOrCreate(['slug' => $slug], [
                'kategorie'      => $kat,
                'titel'          => $titel,
                'focus_keyword'  => $focus,
                'keywords'       => $keywords,
                'such_intention' => $intent[$in],
                'volumen'        => $vol[$v],
                'funnel_wert'    => $vol[$f],
                'prioritaet'     => $score,
                'wort_ziel'      => $f === 'h' ? 1200 : 1000,
                'vorhanden'      => false,
                'status'         => 'geplant',
                'notiz'          => 'Wettbewerber-Lücke ('.$herkunft.')',
            ]);
            $n++; $exists ?: $neu++;
        }

        $this->info("Wettbewerber-Lücken eingespielt: $n (davon neu: $neu)");
        $this->line('Gesamt Themen: '.RatgeberThema::count().' · geplant: '.RatgeberThema::where('status', 'geplant')->count());
        return self::SUCCESS;
    }
}
