<?php

namespace App\Console\Commands;

use App\Models\Gemeinde;
use App\Models\Ortbild;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Recherchiert je Stadt Wahrzeichen-Fotos über die Openverse-API (offene,
 * kostenlose API von WordPress/Creative Commons; aggregiert u. a. Flickr und
 * Wikimedia). Es werden NUR kommerziell nutzbare CC-Lizenzen berücksichtigt
 * (kein NC, kein ND). Legt die Treffer als Kandidaten (rolle=kandidat) in
 * `ortbilder` ab. Im Filament-Admin wählt man je Stadt Hero + Footer;
 * `ortbilder:download` lädt die Auswahl lokal herunter.
 *
 * Bewusst NICHT die Flickr-API: deren Nutzungsbedingungen verlangen für
 * kommerzielle Anwendungen eine Freigabe. Openverse hat diese Einschränkung
 * nicht und liefert dieselben (auch Flickr-)Bilder samt Attribution.
 */
class OrtbilderFetch extends Command
{
    protected $signature = 'ortbilder:fetch
        {--stadt= : Nur eine Stadt (Gemeinde-Slug), sonst alle Top-20}
        {--pro-stadt=6 : Anzahl Kandidaten je Stadt}
        {--min-breite=1200 : Mindest-Bildbreite in px}
        {--nur-frei : Nur frei bearbeitbare Lizenzen (CC BY, CC0, PDM) – ohne Share-Alike}';

    protected $description = 'Holt Wahrzeichen-Bilder der 20 größten Städte via Openverse (nur kommerziell nutzbare CC-Lizenzen).';

    /** Openverse-Lizenzcodes → Kurzname (Version wird angehängt). */
    private const LIZENZ_NAMEN = [
        'by'    => 'CC BY',
        'by-sa' => 'CC BY-SA',
        'cc0'   => 'CC0',
        'pdm'   => 'Public Domain Mark',
    ];

    /** Motive, die keine repräsentative Stadtansicht sind (Innenraum, Detail, Baustelle …). */
    private const AUSSCHLUSS = '/grabmal|gisant|epitaph|sarkophag|reliquie|schrein|altar|kanzel|orgel|'
        .'glasfenster|kirchenfenster|innenraum|interieur|detail|nahaufnahme|skulptur|statue|bauarbeiten|'
        .'baustelle|gerüst|geruest|karte|\bmap\b|grundriss|wappen|briefmarke|modell|panorama.?kugel|'
        .'\bbar im\b|u-?bahn|s-?bahn|bahnhof|hinweisschild|schild|kaisersaal|\bsaal\b|brikett?|lignit|'
        .'exponat|vitrine|ausstellung|gedenktafel|plakette|innen\b/i';

    /** Kuratierte Wahrzeichen-Suchbegriffe je Gemeinde-Slug (Top-20 nach Einwohnern). */
    private const STAEDTE = [
        'berlin'            => ['Brandenburger Tor Berlin', 'Reichstag Berlin', 'Berliner Fernsehturm'],
        'hamburg'           => ['Elbphilharmonie Hamburg', 'Speicherstadt Hamburg', 'Hamburg Landungsbrücken'],
        'muenchen'          => ['Marienplatz München', 'Frauenkirche München', 'Neues Rathaus München'],
        'koeln'             => ['Kölner Dom', 'Hohenzollernbrücke Köln', 'Köln Altstadt Rhein'],
        'frankfurt-am-main' => ['Frankfurt Skyline', 'Römer Frankfurt', 'Frankfurt Mainufer'],
        'stuttgart'         => ['Schlossplatz Stuttgart', 'Neues Schloss Stuttgart', 'Stuttgart Innenstadt'],
        'duesseldorf'       => ['Rheinturm Düsseldorf', 'Medienhafen Düsseldorf', 'Düsseldorf Altstadt'],
        'leipzig'           => ['Völkerschlachtdenkmal Leipzig', 'Leipzig Marktplatz', 'Altes Rathaus Leipzig'],
        'dortmund'          => ['Dortmunder U', 'Signal Iduna Park Dortmund', 'Florianturm Dortmund'],
        'essen'             => ['Zeche Zollverein Essen', 'Essen Innenstadt', 'Grugapark Essen'],
        'bremen'            => ['Bremer Stadtmusikanten', 'Bremer Rathaus Roland', 'Bremen Marktplatz'],
        'dresden'           => ['Frauenkirche Dresden', 'Zwinger Dresden', 'Semperoper Dresden'],
        'hannover'          => ['Neues Rathaus Hannover', 'Hannover Maschsee', 'Marktkirche Hannover'],
        'nuernberg'         => ['Kaiserburg Nürnberg', 'Nürnberg Altstadt', 'Hauptmarkt Nürnberg'],
        'duisburg'          => ['Landschaftspark Duisburg-Nord', 'Innenhafen Duisburg', 'Duisburg Hafen'],
        'bochum'            => ['Deutsches Bergbau-Museum Bochum', 'Jahrhunderthalle Bochum', 'Bochum Rathaus'],
        'wuppertal'         => ['Schwebebahn Wuppertal', 'Historische Stadthalle Wuppertal', 'Wuppertal Innenstadt'],
        'bielefeld'         => ['Sparrenburg Bielefeld', 'Bielefeld Altstadt', 'Rathaus Bielefeld'],
        'bonn'              => ['Poppelsdorfer Schloss Bonn', 'Bonner Münster', 'Altes Rathaus Bonn'],
        'muenster'          => ['Prinzipalmarkt Münster', 'St.-Paulus-Dom Münster', 'Münster Altstadt'],

        // Städte 21–50 nach Einwohnerzahl
        'mannheim'             => ['Wasserturm Mannheim', 'Mannheimer Schloss', 'Mannheim Innenstadt'],
        'karlsruhe'            => ['Karlsruher Schloss', 'Marktplatz Karlsruhe Pyramide', 'Karlsruhe Innenstadt'],
        'augsburg'             => ['Augsburger Rathaus Perlachturm', 'Fuggerei Augsburg', 'Augsburg Rathausplatz'],
        'wiesbaden'            => ['Kurhaus Wiesbaden', 'Marktkirche Wiesbaden', 'Wiesbaden Schlossplatz'],
        'moenchengladbach'     => ['Münster St. Vitus Mönchengladbach', 'Schloss Rheydt', 'Mönchengladbach Abteiberg'],
        'gelsenkirchen'        => ['Veltins-Arena Gelsenkirchen', 'Schloss Horst Gelsenkirchen', 'Nordsternpark Gelsenkirchen'],
        'braunschweig'         => ['Braunschweiger Dom', 'Burg Dankwarderode Braunschweig', 'Braunschweig Burgplatz'],
        'aachen'               => ['Aachener Dom', 'Aachener Rathaus', 'Aachen Elisenbrunnen'],
        'kiel'                 => ['Kieler Förde', 'Kiel Rathausturm', 'Kiel Hörn Brücke'],
        'chemnitz'             => ['Karl-Marx-Monument Chemnitz', 'Roter Turm Chemnitz', 'Chemnitz Rathaus'],
        'halle-saale'          => ['Marktplatz Halle Saale Marktkirche', 'Burg Giebichenstein Halle', 'Halle Saale Roter Turm'],
        'magdeburg'            => ['Magdeburger Dom', 'Grüne Zitadelle Magdeburg', 'Magdeburg Elbufer'],
        'freiburg-im-breisgau' => ['Freiburger Münster', 'Freiburg Altstadt', 'Freiburg Rathausplatz'],
        'krefeld'              => ['Burg Linn Krefeld', 'Krefeld Rathaus', 'Krefeld Innenstadt'],
        'mainz'                => ['Mainzer Dom', 'Mainz Marktplatz', 'Mainz Rheinufer'],
        'luebeck'              => ['Holstentor Lübeck', 'Lübeck Altstadt', 'Lübecker Marienkirche'],
        'erfurt'               => ['Erfurter Dom', 'Krämerbrücke Erfurt', 'Erfurt Fischmarkt'],
        'oberhausen'           => ['Gasometer Oberhausen', 'Schloss Oberhausen', 'Oberhausen Innenstadt'],
        'rostock'              => ['Warnemünde Leuchtturm Rostock', 'Rostock Marienkirche', 'Rostock Neuer Markt'],
        'kassel'               => ['Herkules Kassel', 'Schloss Wilhelmshöhe Kassel', 'Bergpark Kassel'],
        'hagen'                => ['Schloss Hohenlimburg Hagen', 'Freilichtmuseum Hagen', 'Hagen Rathaus'],
        'potsdam'              => ['Schloss Sanssouci Potsdam', 'Cecilienhof Potsdam', 'Potsdam Holländisches Viertel'],
        'saarbruecken'         => ['Saarbrücker Schloss', 'Ludwigskirche Saarbrücken', 'Alte Brücke Saarbrücken'],
        'hamm'                 => ['Maximilianpark Hamm Glaselefant', 'Schloss Oberwerries Hamm', 'Hamm Pauluskirche'],
        'ludwigshafen-am-rhein'=> ['Ludwigshafen Rheinufer', 'Ludwigshafen Pfalzbau', 'Ludwigshafen Innenstadt'],
        'oldenburg'            => ['Oldenburger Schloss', 'Lambertikirche Oldenburg', 'Lappan Oldenburg'],
        'muelheim-an-der-ruhr' => ['Schloss Broich Mülheim', 'Wasserbahnhof Mülheim Ruhr', 'Mülheim an der Ruhr Innenstadt'],
        'osnabrueck'           => ['Osnabrück Rathaus', 'Osnabrücker Dom', 'Schloss Osnabrück'],
        'leverkusen'           => ['Schloss Morsbroich Leverkusen', 'BayArena Leverkusen', 'Japanischer Garten Leverkusen'],
        'solingen'             => ['Schloss Burg Solingen', 'Müngstener Brücke Solingen', 'Solingen Innenstadt'],
    ];

    public function handle(): int
    {
        // Kommerziell nutzbar; standardmäßig inkl. Share-Alike (BY-SA) – für
        // unbearbeitete Anzeige unproblematisch. --nur-frei = frei bearbeitbar.
        $lizenzen  = $this->option('nur-frei') ? 'by,cc0,pdm' : 'by,by-sa,cc0,pdm';
        $proStadt  = max(1, (int) $this->option('pro-stadt'));
        $minBreite = max(0, (int) $this->option('min-breite'));

        $staedte = self::STAEDTE;
        if ($slug = $this->option('stadt')) {
            if (! isset($staedte[$slug])) {
                $this->error("Unbekannter Stadt-Slug '$slug'. Verfügbar: ".implode(', ', array_keys($staedte)));
                return self::FAILURE;
            }
            $staedte = [$slug => $staedte[$slug]];
        }

        $gesamt = 0;
        foreach ($staedte as $slug => $begriffe) {
            $g = Gemeinde::where('slug', $slug)->first();
            if (! $g) {
                $this->warn("  Gemeinde '$slug' nicht in DB – übersprungen.");
                continue;
            }

            $this->info("→ {$g->name} ({$slug})");

            // Treffer je Suchbegriff getrennt sammeln …
            $proBegriff = [];
            foreach ($begriffe as $begriff) {
                $proBegriff[$begriff] = $this->suche($begriff, $lizenzen, $minBreite);
            }
            // … dann im Round-Robin mischen, damit alle Wahrzeichen vertreten sind.
            $gesammelt = [];        // extern_id => record
            for ($runde = 0; count($gesammelt) < $proStadt; $runde++) {
                $etwasHinzu = false;
                foreach ($begriffe as $begriff) {
                    $foto = $proBegriff[$begriff][$runde] ?? null;
                    if (! $foto || isset($gesammelt[$foto['extern_id']])) continue;
                    $foto['wahrzeichen'] = $begriff;
                    $gesammelt[$foto['extern_id']] = $foto;
                    $etwasHinzu = true;
                    if (count($gesammelt) >= $proStadt) break;
                }
                if (! $etwasHinzu) break;
            }

            if (! $gesammelt) {
                $this->warn('  keine passenden Bilder gefunden.');
                continue;
            }

            $i = 0;
            foreach ($gesammelt as $foto) {
                // Bereits ausgewählte Bilder (hero/footer) nicht überschreiben.
                $vorhanden = Ortbild::where('gemeinde_id', $g->id)->where('extern_id', $foto['extern_id'])->first();
                if ($vorhanden && in_array($vorhanden->rolle, ['hero', 'footer'])) {
                    $i++;
                    continue;
                }
                Ortbild::updateOrCreate(
                    ['gemeinde_id' => $g->id, 'extern_id' => $foto['extern_id']],
                    array_merge($foto, ['rolle' => 'kandidat', 'sort' => $i++]),
                );
                $gesamt++;
            }
            $this->line('  '.count($gesammelt).' Kandidaten gespeichert.');
        }

        $this->newLine();
        $this->info("Fertig. $gesamt Kandidaten aktualisiert/angelegt. Auswahl im Admin unter „Ortsbilder“.");
        return self::SUCCESS;
    }

    /** Ein Openverse-Suchlauf → normalisierte Foto-Records. */
    private function suche(string $text, string $lizenzen, int $minBreite): array
    {
        $req = Http::timeout(30)->retry(2, 800)
            ->withHeaders(['User-Agent' => 'WKR-Portal/1.0 (+'.config('app.url').')']);
        if ($token = config('services.openverse.token')) {
            $req = $req->withToken($token);
        }

        $resp = $req->get('https://api.openverse.org/v1/images/', [
            'q'             => $text,
            'license'       => $lizenzen,
            'size'          => 'large',
            'aspect_ratio'  => 'wide',       // Querformat – passt zu Hero/Footer
            'mature'        => 'false',
            'page_size'     => 20,
        ]);

        if (! $resp->ok()) {
            $this->warn('  Openverse-Fehler bei "'.$text.'": HTTP '.$resp->status());
            return [];
        }

        $out = [];
        foreach ($resp->json('results', []) as $r) {
            $w = (int) ($r['width'] ?? 0);
            if (empty($r['url']) || empty($r['id'])) continue;
            if ($w && $w < $minBreite) continue;

            // Titel säubern ("File:"-Präfix + Endung) und Innenraum-/Detailmotive aussortieren.
            $titel = trim(preg_replace(['/^File:\s*/i', '/\.(jpe?g|png|tiff?)$/i'], '', $r['title'] ?? ''));
            if ($titel !== '' && preg_match(self::AUSSCHLUSS, $titel)) continue;

            $lizName = self::LIZENZ_NAMEN[$r['license'] ?? ''] ?? Str::upper($r['license'] ?? '');
            $version = $r['license_version'] ?? '';
            $lizVoll = trim($lizName.' '.$version);

            $out[] = [
                'extern_id'    => (string) $r['id'],
                'provider'     => $r['source'] ?? $r['provider'] ?? null,
                'external_url' => $r['url'],
                'thumb_url'    => Ortbild::thumbFuer($r['url'], 320),
                'quelle'       => $r['foreign_landing_url'] ?? null,
                'titel'        => $titel ?: null,
                'autor'        => trim($r['creator'] ?? '') ?: null,
                'autor_url'    => $r['creator_url'] ?? null,
                'lizenz'       => $lizVoll ?: null,
                'lizenz_url'   => $r['license_url'] ?? null,
                'width'        => $w ?: null,
                'height'       => (int) ($r['height'] ?? 0) ?: null,
            ];
        }
        return $out;
    }
}
