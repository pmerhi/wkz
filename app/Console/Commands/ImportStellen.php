<?php

namespace App\Console\Commands;

use App\Models\Bundesland;
use App\Models\Zulassungsstelle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImportStellen extends Command
{
    protected $signature = 'import:stellen
        {--file= : Pfad zu einer Overpass-JSON- oder GeoJSON-Datei (Browser-Export)}
        {--fresh : Vorhandene OSM-Stellen vor dem Import löschen}
        {--dry : Nur zählen, nicht schreiben}';

    protected $description = 'Importiert Kfz-Zulassungsstellen aus OpenStreetMap (Overpass-API oder Datei, ODbL).';

    public function handle(): int
    {
        $elements = $this->option('file')
            ? $this->loadFromFile($this->option('file'))
            : $this->loadFromApi();

        if ($elements === null) {
            return self::FAILURE;
        }

        $this->info('Datensätze: '.count($elements));

        if ($this->option('fresh') && ! $this->option('dry')) {
            $deleted = Zulassungsstelle::where('quelle', 'like', 'OpenStreetMap%')->delete();
            $this->warn("Vorhandene OSM-Stellen gelöscht: $deleted");
        }

        $created = 0; $updated = 0; $skipped = 0; $gefiltert = 0;
        foreach ($elements as $el) {
            $t = $el['tags'] ?? [];

            // Falschtreffer aussortieren (government=transportation ist zu breit:
            // fängt Autobahn GmbH, Verkehrsverbünde, Ministerien etc.).
            if (! $this->isZulassungsstelle($t)) { $gefiltert++; continue; }

            $name = $t['name'] ?? null;
            $ort  = $t['addr:city'] ?? null;

            if (! $name && ! $ort) { $skipped++; continue; }
            $name = $name ?: ('Zulassungsstelle '.$ort);

            $lat = $el['lat'] ?? ($el['center']['lat'] ?? null);
            $lng = $el['lon'] ?? ($el['center']['lon'] ?? null);

            $strasse = trim(($t['addr:street'] ?? '').' '.($t['addr:housenumber'] ?? '')) ?: null;
            $opening = $t['opening_hours'] ?? null;

            $data = [
                'name'             => $name,
                'traeger'          => $t['operator'] ?? null,
                'strasse'          => $strasse,
                'plz'              => $t['addr:postcode'] ?? null,
                'ort'              => $ort,
                'bundesland_id'    => $this->bundeslandId($t['addr:state'] ?? null),
                'lat'              => $lat,
                'lng'              => $lng,
                'telefon'          => $t['phone'] ?? ($t['contact:phone'] ?? null),
                'email'            => $t['email'] ?? ($t['contact:email'] ?? null),
                'website'          => $t['website'] ?? ($t['contact:website'] ?? null),
                'termin_url'       => $t['contact:appointment'] ?? null,
                'oeffnungszeiten'  => $opening ? ['raw' => $opening] : null,
                'quelle'           => 'OpenStreetMap (ODbL)',
                'last_imported_at' => now(),
            ];

            if ($this->option('dry')) { $created++; continue; }

            $existing = Zulassungsstelle::where('name', $name)
                ->when($ort, fn ($q) => $q->where('ort', $ort))->first();

            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                Zulassungsstelle::create($data + ['slug' => $this->uniqueSlug($name, $ort)]);
                $created++;
            }
        }

        $this->info("Fertig. Neu: $created, aktualisiert: $updated, gefiltert (keine Zulassungsstelle): $gefiltert, übersprungen: $skipped, gesamt: ".Zulassungsstelle::count());
        return self::SUCCESS;
    }

    /**
     * Ist das OSM-Objekt eine Kfz-Zulassungsstelle?
     * Präzises Kriterium service:vehicle:registration=yes ODER ein eindeutiger Name.
     */
    private function isZulassungsstelle(array $t): bool
    {
        if (($t['service:vehicle:registration'] ?? null) === 'yes') {
            return true;
        }
        $name = mb_strtolower($t['name'] ?? '');
        if ($name === '') {
            return false;
        }
        foreach (['zulassung', 'kraftfahrzeug', 'straßenverkehrsamt', 'strassenverkehrsamt', 'kfz-'] as $kw) {
            if (str_contains($name, $kw)) {
                return true;
            }
        }
        return false;
    }

    /** Liest Overpass-JSON (elements[]) oder GeoJSON (features[]) aus einer Datei. */
    private function loadFromFile(string $path): ?array
    {
        if (! is_file($path)) {
            $this->error('Datei nicht gefunden: '.$path);
            return null;
        }
        $json = json_decode((string) file_get_contents($path), true);
        if (! is_array($json)) {
            $this->error('Datei ist kein gültiges JSON.');
            return null;
        }

        // Overpass-Rohformat
        if (isset($json['elements'])) {
            $this->info('Format: Overpass-JSON');
            return $json['elements'];
        }

        // GeoJSON (z.B. Overpass-Turbo-Export)
        if (($json['type'] ?? null) === 'FeatureCollection') {
            $this->info('Format: GeoJSON');
            return collect($json['features'] ?? [])->map(function ($f) {
                $coords = $this->geojsonCenter($f['geometry'] ?? []);
                return [
                    'tags'   => $f['properties'] ?? [],
                    'lat'    => $coords[1] ?? null,
                    'lon'    => $coords[0] ?? null,
                ];
            })->all();
        }

        $this->error('Unbekanntes JSON-Format (weder Overpass-elements noch GeoJSON-FeatureCollection).');
        return null;
    }

    /** Grober Mittelpunkt einer GeoJSON-Geometrie. */
    private function geojsonCenter(array $geometry): array
    {
        $type = $geometry['type'] ?? null;
        $c = $geometry['coordinates'] ?? null;
        if ($type === 'Point' && is_array($c)) {
            return $c; // [lng, lat]
        }
        // Erste Koordinate aus verschachtelten Strukturen ziehen
        $flat = $c;
        while (is_array($flat) && isset($flat[0]) && is_array($flat[0])) {
            $flat = $flat[0];
        }
        return is_array($flat) ? $flat : [];
    }

    /** Lädt über die Overpass-API (leichte Bounding-Box-Abfrage, mehrere Mirrors). */
    private function loadFromApi(): ?array
    {
        // Bounding-Box Deutschland (Süd,West,Nord,Ost) statt teurer area-Auflösung.
        $bbox = '47.27,5.87,55.06,15.04';
        $query = <<<OVERPASS
[out:json][timeout:120];
(
  nwr["service:vehicle:registration"="yes"]($bbox);
  nwr["office"="government"]["government"="transportation"]($bbox);
);
out center tags;
OVERPASS;

        $endpoints = [
            'https://overpass-api.de/api/interpreter',
            'https://overpass.private.coffee/api/interpreter',
            'https://overpass.osm.ch/api/interpreter',
            'https://overpass.kumi.systems/api/interpreter',
        ];

        foreach ($endpoints as $url) {
            $this->info('Frage Overpass ab: '.$url);
            try {
                $response = Http::timeout(180)
                    ->withHeaders(['User-Agent' => 'WunschkennzeichenPortal/1.0'])
                    ->asForm()
                    ->post($url, ['data' => $query]);
            } catch (\Throwable $e) {
                $this->warn('  Fehler: '.Str::limit($e->getMessage(), 120));
                continue;
            }

            if ($response->successful() && is_array($response->json('elements'))) {
                return $response->json('elements');
            }
            $this->warn('  HTTP '.$response->status().' — nächster Endpunkt …');
        }

        $this->error('Alle Overpass-Endpunkte fehlgeschlagen. Bitte den Datei-Weg nutzen: --file=…');
        $this->line('Overpass-Turbo-Abfrage (Browser) siehe daten/quellen.md.');
        return null;
    }

    private function bundeslandId(?string $name): ?int
    {
        if (! $name) return null;
        return Bundesland::firstOrCreate(['slug' => \App\Support\Slug::de($name)], ['name' => $name])->id;
    }

    private function uniqueSlug(string $name, ?string $ort): string
    {
        $base = \App\Support\Slug::de($name) ?: \App\Support\Slug::de((string) $ort) ?: 'stelle';
        $slug = $base; $i = 2;
        while (Zulassungsstelle::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }
        return $slug;
    }
}
