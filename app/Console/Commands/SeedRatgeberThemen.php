<?php

namespace App\Console\Commands;

use App\Models\RatgeberThema;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Befüllt die Ratgeber-Themen-Queue (Kategorisierung + Keywords) für den
 * umfangreichsten Wunschkennzeichen-/Zulassungs-Ratgeber. Quellen: bestehende eigene
 * Seite (74-Themen-Taxonomie), unsere 5 Artikel, Wettbewerber, Domänenwissen.
 * Priorität wird aus Suchvolumen-Schätzung, Funnel-Nähe und Suchintention berechnet;
 * bereits vorhandene Themen werden abgewertet (zuerst die Lücken schreiben).
 */
class SeedRatgeberThemen extends Command
{
    protected $signature = 'ratgeber:themen-seed {--frisch : Tabelle vorher leeren}';

    protected $description = 'Spielt Ratgeber-Themen inkl. Keywords ein und priorisiert sie.';

    /** Codes: intention i/k/t · volumen+funnel h/m/n · v=bereits vorhanden. */
    private const THEMEN = [
        // ---------- Wunschkennzeichen (Funnel-Kern) ----------
        ['Wunschkennzeichen', 'Wunschkennzeichen reservieren – Ablauf, Kosten & Dauer', 'wunschkennzeichen reservieren', ['wunschkennzeichen online reservieren', 'kennzeichen reservieren', 'wunschkennzeichen sichern'], 't', 'h', 'h', true],
        ['Wunschkennzeichen', 'Was kostet ein Wunschkennzeichen? Gebühren im Überblick', 'wunschkennzeichen kosten', ['wunschkennzeichen gebühr', 'kosten wunschkennzeichen reservierung', 'reservierungsgebühr kennzeichen'], 'k', 'h', 'h', false],
        ['Wunschkennzeichen', 'Wunschkennzeichen-Bedeutung: beliebte Buchstaben & Zahlen', 'wunschkennzeichen bedeutung', ['kennzeichen bedeutung', 'beliebte wunschkennzeichen', 'kennzeichen ideen'], 'i', 'm', 'm', false],
        ['Wunschkennzeichen', 'Verbotene Wunschkennzeichen: diese Kombinationen sind tabu', 'verbotene wunschkennzeichen', ['verbotene kennzeichen kombinationen', 'hj ss kz kennzeichen', 'welche kennzeichen sind verboten'], 'i', 'm', 'm', false],
        ['Wunschkennzeichen', 'Wunschkennzeichen bei Umzug mitnehmen – geht das?', 'wunschkennzeichen mitnehmen', ['kennzeichenmitnahme', 'kennzeichen behalten umzug', 'kennzeichen mitnehmen anderer kreis'], 'i', 'm', 'h', false],
        ['Wunschkennzeichen', 'Wie lange ist ein reserviertes Wunschkennzeichen gültig?', 'wunschkennzeichen reservierung gültigkeit', ['wunschkennzeichen wie lange gültig', 'reservierungsdauer kennzeichen', 'wunschkennzeichen verfällt'], 'i', 'm', 'h', false],
        ['Wunschkennzeichen', 'Wunschkennzeichen: wie viele Buchstaben & Zahlen sind erlaubt?', 'wunschkennzeichen buchstaben zahlen', ['wie viele zeichen kennzeichen', 'kennzeichen aufbau', 'maximale zeichen kennzeichen'], 'i', 'm', 'm', false],
        ['Wunschkennzeichen', 'Wunschkennzeichen online oder vor Ort reservieren?', 'wunschkennzeichen online vor ort', ['wunschkennzeichen ohne termin', 'kennzeichen online reservieren ablauf'], 't', 'm', 'h', false],
        ['Wunschkennzeichen', 'Wunschkennzeichen für Motorrad & Anhänger', 'wunschkennzeichen motorrad', ['motorrad wunschkennzeichen', 'anhänger wunschkennzeichen'], 'i', 'n', 'h', false],
        ['Wunschkennzeichen', 'Kfz-Kennzeichen Abkürzungen: alle Unterscheidungszeichen', 'kennzeichen abkürzungen', ['kfz kennzeichen liste', 'unterscheidungszeichen', 'kennzeichen kürzel deutschland'], 'i', 'h', 'm', false],
        ['Wunschkennzeichen', 'Altkennzeichen: wieder eingeführte Kfz-Kennzeichen', 'altkennzeichen', ['wieder eingeführte kennzeichen', 'altkennzeichen liste', 'kennzeichenliberalisierung'], 'i', 'm', 'm', false],

        // ---------- Kfz-Zulassung / Anmelden ----------
        ['Kfz-Zulassung', 'Auto anmelden – Unterlagen, Ablauf und Kosten', 'auto anmelden', ['kfz anmelden', 'fahrzeug zulassen', 'auto zulassen unterlagen'], 'i', 'h', 'm', true],
        ['Kfz-Zulassung', 'Neuwagen zulassen: Ablauf und Unterlagen', 'neuwagen zulassen', ['neuwagen anmelden', 'neuwagen zulassung kosten'], 'i', 'm', 'm', false],
        ['Kfz-Zulassung', 'Gebrauchtwagen zulassen – Schritt für Schritt', 'gebrauchtwagen zulassen', ['gebrauchtwagen anmelden', 'gebrauchtwagen ummelden unterlagen'], 'i', 'h', 'm', false],
        ['Kfz-Zulassung', 'Gebrauchtwagen aus der EU importieren und zulassen', 'gebrauchtwagen eu import', ['eu reimport zulassen', 'auto aus eu importieren'], 'i', 'm', 'n', false],
        ['Kfz-Zulassung', 'Fahrzeugimport außerhalb der EU: Zoll & Zulassung', 'auto import nicht eu', ['auto importieren drittland', 'us import auto zulassen'], 'i', 'n', 'n', false],
        ['Kfz-Zulassung', 'Tageszulassung: Vorteile, Kosten und Fallstricke', 'tageszulassung', ['tageszulassung erklärung', 'tageszulassung nachteile'], 'i', 'm', 'm', false],
        ['Kfz-Zulassung', 'Wiederzulassung eines abgemeldeten Fahrzeugs', 'wiederzulassung', ['auto wieder anmelden', 'abgemeldetes auto zulassen'], 'i', 'm', 'm', false],
        ['Kfz-Zulassung', 'Saisonzulassung: so funktioniert das Saisonkennzeichen', 'saisonzulassung', ['saisonkennzeichen zulassung', 'auto saison anmelden'], 'i', 'm', 'm', false],
        ['Kfz-Zulassung', 'Oldtimer zulassen: H-Kennzeichen & rotes 07er', 'oldtimer zulassen', ['oldtimer anmelden', 'oldtimer zulassung voraussetzungen'], 'i', 'm', 'm', false],
        ['Kfz-Zulassung', 'Zulassungsbescheinigung Teil I und II (Fahrzeugschein/-brief)', 'zulassungsbescheinigung', ['fahrzeugschein', 'fahrzeugbrief', 'zulassungsbescheinigung teil 1 verloren'], 'i', 'h', 'm', false],
        ['Kfz-Zulassung', 'eVB-Nummer: elektronische Versicherungsbestätigung', 'evb nummer', ['evb nummer kostenlos', 'evb nummer beantragen', 'was ist eine evb nummer'], 'i', 'h', 'm', false],
        ['Kfz-Zulassung', 'Betriebserlaubnis (ABE/EBE) – was Halter wissen müssen', 'betriebserlaubnis', ['allgemeine betriebserlaubnis', 'abe fahrzeug', 'einzelbetriebserlaubnis'], 'i', 'm', 'n', false],
        ['Kfz-Zulassung', 'Erlöschen der Betriebserlaubnis: Ursachen & Folgen', 'betriebserlaubnis erloschen', ['erlöschen der betriebserlaubnis', 'tuning betriebserlaubnis'], 'i', 'n', 'n', false],
        ['Kfz-Zulassung', 'Fahrzeug-Zulassungsverordnung (FZV) einfach erklärt', 'fahrzeugzulassungsverordnung', ['fzv', 'zulassungsrecht'], 'i', 'n', 'n', false],
        ['Kfz-Zulassung', 'Anhänger zulassen: Kennzeichen, TÜV und Versicherung', 'anhänger zulassen', ['anhänger anmelden', 'anhänger kennzeichen'], 'i', 'm', 'm', false],
        ['Kfz-Zulassung', 'Zulassungsdienst & Vollmacht: Auto zulassen lassen', 'zulassungsdienst vollmacht', ['auto zulassen lassen', 'vollmacht kfz zulassung'], 'i', 'm', 'm', false],
        ['Kfz-Zulassung', 'Finanziertes oder geleastes Fahrzeug zulassen', 'finanziertes fahrzeug zulassen', ['leasing fahrzeug zulassen', 'sicherungsübereignung fahrzeugbrief'], 'i', 'n', 'm', false],
        ['Kfz-Zulassung', 'Umweltplakette & Umweltzonen in Deutschland', 'umweltplakette', ['feinstaubplakette', 'grüne plakette', 'umweltzone'], 'i', 'm', 'n', false],

        // ---------- Ummeldung & Abmeldung ----------
        ['Ummeldung & Abmeldung', 'Auto ummelden – nach Umzug oder Kauf', 'auto ummelden', ['kfz ummelden', 'fahrzeug ummelden unterlagen', 'auto ummelden kosten'], 'i', 'h', 'm', true],
        ['Ummeldung & Abmeldung', 'Auto abmelden – online über i-Kfz und vor Ort', 'auto abmelden', ['kfz abmelden', 'fahrzeug abmelden', 'auto abmelden unterlagen'], 'i', 'h', 'm', true],
        ['Ummeldung & Abmeldung', 'Auto ummelden mit Kreiswechsel', 'auto ummelden kreiswechsel', ['ummelden anderer landkreis', 'kennzeichen wechsel umzug'], 'i', 'm', 'm', false],
        ['Ummeldung & Abmeldung', 'Auto ummelden ohne Kreiswechsel (Adressänderung)', 'auto ummelden ohne kreiswechsel', ['adressänderung fahrzeugschein', 'ummelden gleicher kreis'], 'i', 'm', 'm', false],
        ['Ummeldung & Abmeldung', 'Auto verkaufen: Abmeldung, Ummeldung & Haftung', 'auto verkaufen abmelden', ['auto verkauft ummelden', 'kaufvertrag auto abmeldung'], 'i', 'm', 'm', false],
        ['Ummeldung & Abmeldung', 'Auto stilllegen vs. abmelden – der Unterschied', 'auto stilllegen', ['fahrzeug stilllegen', 'außerbetriebsetzung'], 'i', 'm', 'm', false],
        ['Ummeldung & Abmeldung', 'Namensänderung im Fahrzeugschein (Heirat etc.)', 'namensänderung fahrzeugschein', ['kennzeichen namensänderung', 'fahrzeugschein ändern heirat'], 'i', 'n', 'n', false],
        ['Ummeldung & Abmeldung', 'Fahrzeug verschrotten & Verwertungsnachweis', 'fahrzeug verschrotten', ['auto verschrotten', 'verwertungsnachweis', 'abwrackbescheinigung'], 'i', 'm', 'n', false],
        ['Ummeldung & Abmeldung', 'Auto erben: ummelden, abmelden oder verkaufen', 'auto erben ummelden', ['fahrzeug erbe ummelden', 'auto geerbt was tun'], 'i', 'n', 'm', false],
        ['Ummeldung & Abmeldung', 'Halterwechsel beim Auto richtig durchführen', 'halterwechsel auto', ['fahrzeughalter ändern', 'halterwechsel ohne ummeldung'], 'i', 'm', 'm', false],

        // ---------- i-Kfz & Online ----------
        ['i-Kfz & Online', 'i-Kfz – Auto online zulassen, ab- und ummelden', 'i-kfz', ['internetbasierte fahrzeugzulassung', 'auto online zulassen', 'i-kfz portal'], 'i', 'h', 'h', true],
        ['i-Kfz & Online', 'i-Kfz Stufe 4: was seit 2023 online möglich ist', 'i-kfz stufe 4', ['i-kfz neuzulassung online', 'ikfz halterwechsel online'], 'i', 'm', 'h', false],
        ['i-Kfz & Online', 'i-Kfz Voraussetzungen: eID, Ausweis & Co.', 'i-kfz voraussetzungen', ['i-kfz personalausweis', 'eid ausweis kfz', 'i-kfz benötigte daten'], 'i', 'm', 'h', false],
        ['i-Kfz & Online', 'i-Kfz Ablauf & Dauer: vom Antrag bis zur Plakette', 'i-kfz ablauf dauer', ['i-kfz wie lange', 'i-kfz plakette versand'], 'i', 'm', 'h', false],

        // ---------- Kennzeichen-Arten ----------
        ['Kennzeichen-Arten', 'Kurzzeitkennzeichen: Überführung & Probefahrt', 'kurzzeitkennzeichen', ['kurzzeitkennzeichen beantragen', 'kurzzeitkennzeichen kosten', 'kurzzeitkennzeichen versicherung'], 'i', 'h', 'm', false],
        ['Kennzeichen-Arten', 'Ausfuhrkennzeichen (Zollkennzeichen) beantragen', 'ausfuhrkennzeichen', ['exportkennzeichen', 'zollkennzeichen', 'ausfuhrkennzeichen kosten'], 'i', 'm', 'm', false],
        ['Kennzeichen-Arten', 'Saisonkennzeichen: Kosten, Versicherung & Steuer', 'saisonkennzeichen', ['saisonkennzeichen kosten', 'saisonkennzeichen versicherung'], 'i', 'h', 'm', false],
        ['Kennzeichen-Arten', 'Wechselkennzeichen für zwei Fahrzeuge', 'wechselkennzeichen', ['wechselkennzeichen kosten', 'wechselkennzeichen vorteile'], 'i', 'm', 'm', false],
        ['Kennzeichen-Arten', 'E-Kennzeichen für Elektroautos: Vorteile & Antrag', 'e-kennzeichen', ['elektrokennzeichen', 'e kennzeichen vorteile'], 'i', 'm', 'm', false],
        ['Kennzeichen-Arten', 'H-Kennzeichen & Oldtimer-Kennzeichen', 'h-kennzeichen', ['oldtimer kennzeichen', 'h kennzeichen voraussetzungen', '07 kennzeichen'], 'i', 'm', 'm', false],
        ['Kennzeichen-Arten', 'Rotes Kennzeichen (06er & 07er) für Händler/Oldtimer', 'rotes kennzeichen', ['händlerkennzeichen', '06 kennzeichen', 'rotes 07 kennzeichen'], 'i', 'm', 'n', false],
        ['Kennzeichen-Arten', 'Grünes Kennzeichen: steuerbefreite Fahrzeuge', 'grünes kennzeichen', ['steuerbefreites kennzeichen', 'grünes nummernschild'], 'i', 'm', 'n', false],
        ['Kennzeichen-Arten', '3D-Kennzeichen & Carbon-Kennzeichen: erlaubt?', '3d kennzeichen', ['carbon kennzeichen', '3d nummernschild erlaubt'], 'i', 'm', 'm', false],
        ['Kennzeichen-Arten', 'Motorradkennzeichen: Größe & Vorschriften', 'motorradkennzeichen', ['kennzeichen motorrad größe', 'motorrad nummernschild'], 'i', 'm', 'm', false],
        ['Kennzeichen-Arten', 'Versicherungskennzeichen für Mofa, Roller & E-Bike', 'versicherungskennzeichen', ['mofa kennzeichen', 'rollerkennzeichen', 'versicherungskennzeichen kosten'], 'i', 'm', 'm', false],
        ['Kennzeichen-Arten', 'EU-Kennzeichen & internationale Kennzeichen', 'eu kennzeichen', ['euro kennzeichen', 'länderkennzeichen', 'internationale kfz kennzeichen'], 'i', 'm', 'n', false],
        ['Kennzeichen-Arten', 'Kennzeichen verloren oder gestohlen – was tun?', 'kennzeichen verloren gestohlen', ['nummernschild gestohlen', 'kennzeichen verlust anzeige'], 'i', 'm', 'm', false],
        ['Kennzeichen-Arten', 'Kennzeichengröße & Maße der Nummernschilder', 'kennzeichengröße', ['kennzeichen maße', 'nummernschild größe'], 'i', 'm', 'n', false],
        ['Kennzeichen-Arten', 'Kennzeichen entstempeln: so geht die Abmeldung am Schild', 'kennzeichen entstempeln', ['entstempeln lassen', 'kennzeichen entsiegeln'], 'i', 'n', 'm', false],

        // ---------- Kosten, Steuer & Versicherung ----------
        ['Kosten & Steuer', 'Zulassungskosten 2026: alle Gebühren im Überblick', 'zulassungskosten', ['kfz zulassung kosten', 'was kostet auto anmelden', 'ummeldung kosten'], 'k', 'h', 'm', false],
        ['Kosten & Steuer', 'Kfz-Steuer berechnen: Hubraum, CO₂ & Co.', 'kfz steuer berechnen', ['kfz steuer rechner', 'autosteuer', 'kraftfahrzeugsteuer'], 'k', 'h', 'n', false],
        ['Kosten & Steuer', 'Kfz-Steuer für Elektroautos: Befreiung & Fristen', 'kfz steuer elektroauto', ['e auto steuerbefreiung', 'elektroauto steuer'], 'i', 'm', 'n', false],
        ['Kosten & Steuer', 'SEPA-Lastschriftmandat für die Kfz-Steuer', 'kfz steuer lastschrift', ['einzugsermächtigung kfz steuer', 'sepa mandat kfz steuer'], 'i', 'n', 'n', false],
        ['Kosten & Steuer', 'Kfz-Versicherung vergleichen & wechseln', 'kfz versicherung vergleich', ['autoversicherung vergleich', 'kfz versicherung wechseln', 'sf klassen'], 'k', 'h', 'n', false],

        // ---------- Halter-Tipps & Sonderfälle ----------
        ['Halter-Tipps', 'Bußgeldkatalog: aktuelle Strafen für Fahrzeughalter', 'bußgeldkatalog', ['bußgeldkatalog 2026', 'punkte flensburg', 'strafen falschparken'], 'i', 'h', 'n', false],
        ['Halter-Tipps', 'Kfz-Gutachten: Wert, Schaden & Oldtimer', 'kfz gutachten', ['fahrzeuggutachten', 'wertgutachten auto'], 'i', 'm', 'n', false],
        ['Halter-Tipps', 'Betrug beim Autokauf & -verkauf vermeiden', 'betrug autokauf', ['autokauf betrug', 'sicher auto verkaufen'], 'i', 'm', 'n', false],
        ['Halter-Tipps', 'Gebrauchtwagengarantie & Sachmängelhaftung', 'gebrauchtwagengarantie', ['sachmängelhaftung auto', 'gewährleistung gebrauchtwagen'], 'i', 'm', 'n', false],
        ['Halter-Tipps', 'Verbandskasten & Warnweste: Pflicht & Fristen', 'verbandskasten pflicht', ['warnweste pflicht', 'verbandskasten ablaufdatum'], 'i', 'm', 'n', false],
        ['Halter-Tipps', 'Führerschein ab 17: Begleitetes Fahren (BF17)', 'führerschein ab 17', ['begleitetes fahren', 'bf17'], 'i', 'm', 'n', false],
        ['Halter-Tipps', 'Hauptuntersuchung (TÜV): Ablauf, Kosten & Fristen', 'hauptuntersuchung tüv', ['tüv fällig', 'hu au', 'tüv kosten'], 'i', 'h', 'n', false],
    ];

    public function handle(): int
    {
        if ($this->option('frisch')) RatgeberThema::query()->delete();

        $vol = ['h' => 'hoch', 'm' => 'mittel', 'n' => 'niedrig'];
        $intent = ['i' => 'informational', 'k' => 'kommerziell', 't' => 'transaktional'];

        $n = 0;
        foreach (self::THEMEN as [$kat, $titel, $focus, $keywords, $in, $v, $f, $vorhanden]) {
            $prio = $this->prioritaet($v, $f, $in, $vorhanden);
            RatgeberThema::updateOrCreate(
                ['slug' => \App\Support\Slug::de($focus)],
                [
                    'kategorie'      => $kat,
                    'titel'          => $titel,
                    'focus_keyword'  => $focus,
                    'keywords'       => $keywords,
                    'such_intention' => $intent[$in],
                    'volumen'        => $vol[$v],
                    'funnel_wert'    => $vol[$f],
                    'prioritaet'     => $prio,
                    'wort_ziel'      => $f === 'h' ? 1200 : 1000,
                    'vorhanden'      => $vorhanden,
                    'status'         => $vorhanden ? 'fertig' : 'geplant',
                ]
            );
            $n++;
        }

        $this->info("Ratgeber-Themen eingespielt/aktualisiert: $n");
        foreach (RatgeberThema::selectRaw('kategorie, COUNT(*) n, SUM(vorhanden) vorhanden')->groupBy('kategorie')->orderByDesc('n')->get() as $r) {
            $this->line(sprintf('  %-26s %2d Themen (%d vorhanden)', $r->kategorie, $r->n, $r->vorhanden));
        }
        return self::SUCCESS;
    }

    /** Priorität 0–100 aus Funnel-Nähe, Suchvolumen, Intention; Vorhandenes abgewertet. */
    private function prioritaet(string $vol, string $funnel, string $intent, bool $vorhanden): int
    {
        $score = ['h' => 40, 'm' => 24, 'n' => 10][$funnel]
               + ['h' => 30, 'm' => 18, 'n' => 8][$vol]
               + ['t' => 18, 'k' => 12, 'i' => 6][$intent];
        if ($vorhanden) $score -= 45;   // existiert schon → niedrigere Schreib-Priorität
        return max(0, min(100, $score));
    }
}
