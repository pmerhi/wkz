<?php

namespace App\Http\Controllers;

use App\Models\Bundesland;
use App\Models\KennzeichenKuerzel;
use App\Models\RatgeberArtikel;
use App\Models\Zulassungsstelle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function home()
    {
        $stellen = Zulassungsstelle::indexable()->orderBy('name')->limit(12)->get();
        $kuerzel = KennzeichenKuerzel::orderBy('code')->limit(24)->get();
        $artikel = RatgeberArtikel::whereNotNull('published_at')
            ->orderByDesc('published_at')->limit(6)->get();

        $schemas = [[
            '@context' => 'https://schema.org',
            '@type'    => 'WebSite',
            'name'     => config('portal.site_name'),
            'url'      => url('/'),
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => url('/zulassungsstelle').'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ]];

        return view('pages.home', [
            'title'       => 'Wunschkennzeichen reservieren — Verfügbarkeit prüfen & Zulassungsstelle finden',
            'description' => 'Wunschkennzeichen online prüfen und reservieren, Zulassungsstellen mit Öffnungszeiten und Terminvergabe finden sowie Ratgeber rund um Kfz-Zulassung.',
            'canonical'   => url('/'),
            'schemas'     => $schemas,
            'stellen'     => $stellen,
            'kuerzel'     => $kuerzel,
            'artikel'     => $artikel,
        ]);
    }

    public function zulassungsstelleIndex(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $breadcrumb = $this->breadcrumb([
            ['Start', url('/')],
            ['Zulassungsstellen', url('/zulassungsstelle')],
        ]);

        // Suche (?q=) — Ergebnisseite, nicht indexieren, Canonical aufs Verzeichnis.
        if ($q !== '') {
            $treffer = Zulassungsstelle::with('bundesland')->whereNull('parent_id')
                ->where(fn ($x) => $x->where('name', 'like', "%{$q}%")->orWhere('ort', 'like', "%{$q}%"))
                ->orderBy('name')->limit(50)->get();

            return view('pages.zulassungsstelle-index', [
                'title'       => 'Zulassungsstelle suchen: '.$q,
                'description' => 'Suchergebnisse für Zulassungsstellen.',
                'canonical'   => url('/zulassungsstelle'),
                'robots'      => 'noindex,follow',
                'schemas'     => [$breadcrumb],
                'q'           => $q,
                'treffer'     => $treffer,
                'laender'     => null,
            ]);
        }

        // Hub: nur die Bundesländer (Hub-and-Spoke) — Detail-Listen liegen je Land.
        $laender = Bundesland::has('zulassungsstellen')
            ->withCount(['zulassungsstellen' => fn ($q) => $q->whereNull('parent_id')])
            ->orderBy('name')->get();

        $items = [];
        foreach ($laender as $i => $land) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $land->name,
                'url'      => url('/zulassungsstelle/'.$land->slug),
            ];
        }

        return view('pages.zulassungsstelle-index', [
            'title'       => 'Zulassungsstellen in Deutschland — nach Bundesland',
            'description' => 'Verzeichnis der Kfz-Zulassungsstellen, gegliedert nach Bundesland — mit Anschrift, Öffnungszeiten und Online-Terminvergabe.',
            'canonical'   => url('/zulassungsstelle'),
            'schemas'     => [
                ['@context' => 'https://schema.org', '@type' => 'ItemList', 'itemListElement' => $items],
                $breadcrumb,
            ],
            'q'           => '',
            'treffer'     => null,
            'laender'     => $laender,
        ]);
    }

    public function zulassungsstelle(string $land, string $slug)
    {
        $query = Zulassungsstelle::with(['bundesland', 'kennzeichenKuerzel', 'seoMeta', 'gemeinde',
            'kinder' => fn ($q) => $q->with('bundesland')]);
        if ($land === 'deutschland') {
            $query->whereNull('bundesland_id');
        } else {
            $bl = Bundesland::where('slug', $land)->firstOrFail();
            $query->where('bundesland_id', $bl->id);
        }
        $stelle = $query->where('slug', $slug)->firstOrFail();

        // Kind-Stelle (Außenstelle) → auf das Primär-Amt des Ortes weiterleiten.
        if ($stelle->parent_id) {
            return redirect($stelle->parent->pfad, 301);
        }
        // Falsches Land-Segment → auf die kanonische URL umleiten.
        if ($stelle->land_slug !== $land) {
            return redirect($stelle->pfad, 301);
        }

        // Kuratierte, für jede Zulassungsstelle relevante Ratgeber (feste Reihenfolge).
        $ratgeberSlugs = [
            'wunschkennzeichen-reservieren', 'wunschkennzeichen-kosten', 'auto-anmelden',
            'i-kfz-online-zulassung', 'zulassungskosten', 'auto-ummelden', 'auto-abmelden',
            'evb-nummer', 'gebrauchtwagen-zulassen', 'kurzzeitkennzeichen',
        ];
        $artikel = RatgeberArtikel::with('kategorie')
            ->whereNotNull('published_at')->whereIn('slug', $ratgeberSlugs)->get()
            ->sortBy(fn ($a) => array_search($a->slug, $ratgeberSlugs))->values();

        $office = array_filter([
            '@context'         => 'https://schema.org',
            '@type'            => 'GovernmentOffice',
            '@id'              => $stelle->url().'#office',
            'name'             => $stelle->name,
            // url = offizielle Behörden-Website (Entitäts-URL), nicht unsere Seite
            'url'              => $stelle->website ?: null,
            // unsere Verzeichnisseite beschreibt die Entität (statt sich als sie auszugeben)
            'mainEntityOfPage' => $stelle->url(),
            'telephone'        => $stelle->telefon,
            'email'            => $stelle->email,
            'address'          => array_filter([
                '@type'           => 'PostalAddress',
                'streetAddress'   => $stelle->strasse,
                'postalCode'      => $stelle->plz,
                'addressLocality' => $stelle->ort,
                'addressRegion'   => $stelle->bundesland?->name,
                'addressCountry'  => 'DE',
            ]),
            'areaServed'       => $stelle->traeger
                ? ['@type' => 'AdministrativeArea', 'name' => $stelle->traeger]
                : null,
            // AGS als eindeutiges Entitäts-Identifikationsmerkmal (AGS-Prinzip)
            'identifier'       => $stelle->gemeinde
                ? ['@type' => 'PropertyValue', 'propertyID' => 'AGS', 'value' => $stelle->gemeinde->ags]
                : null,
        ]);
        if ($stelle->lat && $stelle->lng) {
            $office['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) $stelle->lat, 'longitude' => (float) $stelle->lng];
        }
        if (is_array($stelle->oeffnungszeiten)) {
            $spec = [];
            foreach ($stelle->oeffnungszeiten as $z) {
                if (! is_array($z) || ! isset($z['day'], $z['opens'], $z['closes'])) continue;
                $spec[] = [
                    '@type'     => 'OpeningHoursSpecification',
                    'dayOfWeek' => $z['day'],
                    'opens'     => $z['opens'],
                    'closes'    => $z['closes'],
                ];
            }
            if ($spec) $office['openingHoursSpecification'] = $spec;
        }

        $crumbs = [
            ['Start', url('/')],
            ['Zulassungsstellen', url('/zulassungsstelle')],
        ];
        if ($stelle->bundesland) {
            $crumbs[] = [$stelle->bundesland->name, url('/zulassungsstelle/'.$stelle->bundesland->slug)];
        }
        $crumbs[] = [$stelle->name, $stelle->url()];

        $schemas = [$office, $this->breadcrumb($crumbs)];

        // FAQ – eine Quelle für sichtbare <details> und FAQPage-Schema.
        $ortLabel  = $stelle->ort ?: $stelle->name;
        $reservUrl = config('portal.reservation_url').'?utm_source=portal&utm_medium=cta&utm_campaign=zst&zst='.$stelle->slug;
        $terminAntwort = $stelle->termin_url
            ? 'Ja, wir empfehlen eine <a href="'.e($stelle->termin_url).'" rel="nofollow noopener" target="_blank">Online-Terminbuchung</a>, um Wartezeiten zu vermeiden.'
            : 'Viele Zulassungsstellen arbeiten mit Terminvergabe. Bitte informiere dich vorab auf der offiziellen Website.';
        $faq = [
            ['Wie reserviere ich ein Wunschkennzeichen in '.$ortLabel.'?',
             'Prüfe online die Verfügbarkeit deiner Wunsch-Kombination und reserviere sie. Anschließend '
             .'kannst du das Kennzeichen bei der '.e($stelle->name).' zur Zulassung verwenden. Wie das Schritt '
             .'für Schritt läuft, steht im <a href="'.url('/ratgeber/wunschkennzeichen-reservieren').'">Ratgeber '
             .'zum Wunschkennzeichen reservieren</a>. <a href="'.e($reservUrl).'" rel="nofollow">Jetzt prüfen &amp; reservieren →</a>'],
            ['Brauche ich für die Zulassung in '.$ortLabel.' einen Termin?',
             $terminAntwort],
            ['Kann ich mein Auto in '.$ortLabel.' online zulassen?',
             'Ja – über das i-Kfz-Portal sind An-, Ab- und Ummeldung digital möglich. Wie das genau '
             .'funktioniert, erklären wir im <a href="'.url('/ratgeber/i-kfz-online-zulassung').'">i-Kfz-Ratgeber</a>.'],
        ];
        $schemas[] = $this->faqPage($faq);

        // Dünne Stubs (nur Name + Geo) nicht indexieren.
        $noindex = $stelle->seoMeta?->noindex || ! $stelle->is_indexable;

        return view('pages.zulassungsstelle', [
            'title'       => $stelle->seoMeta?->title ?? ($stelle->name.' — Öffnungszeiten, Termin & Wunschkennzeichen'),
            'description' => $stelle->seoMeta?->description ?? ('Zulassungsstelle '.$stelle->name.': Anschrift, Öffnungszeiten, Terminvergabe und Wunschkennzeichen reservieren.'),
            'canonical'   => $stelle->seoMeta?->canonical ?? $stelle->url(),
            'robots'      => $noindex ? 'noindex,follow' : 'index,follow',
            'schemas'     => $schemas,
            'stelle'      => $stelle,
            'artikel'     => $artikel,
            'faq'         => $faq,
        ]);
    }

    public function kuerzelIndex()
    {
        $kuerzel = KennzeichenKuerzel::indexable()->orderBy('code')->get()
            ->groupBy(fn ($k) => Str::upper(Str::substr($k->code, 0, 1)));

        $altkennzeichen = KennzeichenKuerzel::altkennzeichen()->orderBy('code')->get();

        $schemas = [$this->breadcrumb([
            ['Start', url('/')],
            ['Kennzeichen', url('/kennzeichen')],
        ])];

        return view('pages.kennzeichen-index', [
            'title'          => 'Kfz-Kennzeichen Liste — alle Unterscheidungszeichen A–Z',
            'description'    => 'Liste aller deutschen Kfz-Kennzeichen (Unterscheidungszeichen) von A bis Z — mit Stadt/Landkreis, wieder eingeführten Altkennzeichen und Wunschkennzeichen-Reservierung.',
            'canonical'      => url('/kennzeichen'),
            'schemas'        => $schemas,
            'gruppen'        => $kuerzel,
            'altkennzeichen' => $altkennzeichen,
        ]);
    }

    public function altkennzeichen()
    {
        $laender = Bundesland::orderBy('name')->get();

        // Altkennzeichen nach Bundesland gruppieren (aus konsolidierter Bedeutung abgeleitet).
        $alle = KennzeichenKuerzel::altkennzeichen()->orderBy('code')->get();
        $gruppen = [];
        $ohne = [];
        foreach ($alle as $k) {
            $land = $laender->first(fn ($b) => $k->bedeutung && str_contains($k->bedeutung, $b->name));
            if ($land) {
                $gruppen[$land->name]['land'] = $land;
                $gruppen[$land->name]['codes'][] = $k;
            } else {
                $ohne[] = $k;
            }
        }
        ksort($gruppen);

        $faq = [
            ['Was sind Altkennzeichen?',
             'Altkennzeichen sind Kfz-Unterscheidungszeichen aufgelöster Land- und Stadtkreise, die nach den Gebietsreformen ausliefen und im Rahmen der Kennzeichenliberalisierung seit dem 1. November 2012 wieder ausgegeben werden dürfen.'],
            ['Warum wurden Altkennzeichen wieder eingeführt?',
             'Aus regionaler Verbundenheit: Bürgerinnen und Bürger können wieder das Kennzeichen ihrer Heimatstadt oder ihres früheren Landkreises führen. Die zuständige Zulassungsstelle des heutigen Verwaltungsbezirks vergibt sie auf Wunsch.'],
            ['Kann ich ein Altkennzeichen als Wunschkennzeichen reservieren?',
             'Ja. Ein wieder eingeführtes Altkennzeichen lässt sich wie jedes andere Unterscheidungszeichen als Wunschkennzeichen reservieren, sofern die gewünschte Buchstaben-Zahlen-Kombination noch frei ist.'],
            ['Wie viele Altkennzeichen gibt es?',
             'Aktuell führt dieses Verzeichnis '.$alle->count().' wieder eingeführte Altkennzeichen in ganz Deutschland.'],
        ];

        $schemas = [
            $this->breadcrumb([
                ['Start', url('/')],
                ['Kennzeichen', url('/kennzeichen')],
                ['Altkennzeichen', url('/altkennzeichen')],
            ]),
            [
                '@context' => 'https://schema.org',
                '@type'    => 'FAQPage',
                'mainEntity' => array_map(fn ($f) => [
                    '@type'          => 'Question',
                    'name'           => $f[0],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
                ], $faq),
            ],
        ];

        return view('pages.altkennzeichen', [
            'title'       => 'Altkennzeichen: Liste der wieder eingeführten Kfz-Kennzeichen ('.$alle->count().')',
            'description' => 'Alle '.$alle->count().' wieder eingeführten Altkennzeichen in Deutschland nach Bundesland – mit historischer Bedeutung, heutigem Zulassungsbezirk und Wunschkennzeichen-Reservierung.',
            'canonical'   => url('/altkennzeichen'),
            'schemas'     => $schemas,
            'gruppen'     => $gruppen,
            'ohne'        => $ohne,
            'anzahl'      => $alle->count(),
            'faq'         => $faq,
        ]);
    }

    public function kuerzel(string $slug)
    {
        $k = KennzeichenKuerzel::with('zulassungsstellen.bundesland')
            ->where('slug', $slug)->firstOrFail();

        // Bundesland aus zugeordneten Stellen ableiten (für Anreicherung).
        $bundesland = $k->zulassungsstellen
            ->map(fn ($s) => $s->bundesland)->filter()->first();

        // Fallback ohne Stelle: Bundesland aus der konsolidierten Bedeutung ableiten
        // („Kreis, Bundesland") und verlinken — schließt interne Lücken bei Orphan-Kürzeln.
        if (! $bundesland && $k->bedeutung) {
            $bundesland = Bundesland::all()->first(fn ($b) => str_contains($k->bedeutung, $b->name));
        }

        $schemas = [$this->breadcrumb([
            ['Start', url('/')],
            ['Kennzeichen', url('/kennzeichen')],
            [$k->code, url('/kennzeichen/'.$k->slug)],
        ])];

        $noindex = ! $k->bedeutung && $k->zulassungsstellen->isEmpty();

        $altHint = $k->ist_altkennzeichen
            ? ' Altkennzeichen'.($k->historische_stadt ? ' ('.$k->historische_stadt.')' : '').' – seit 2012 wieder erhältlich.'
            : '';

        return view('pages.kuerzel', [
            'title'       => 'Kennzeichen '.$k->code.($k->bedeutung ? ' — '.$k->bedeutung : '').($k->ist_altkennzeichen ? ' (Altkennzeichen)' : '').' | Wunschkennzeichen reservieren',
            'description' => 'Das Kfz-Kennzeichen '.$k->code.' steht für '.($k->bedeutung ?: 'einen Zulassungsbezirk').'.'.$altHint.' Zuständige Zulassungsstelle, Bundesland und Wunschkennzeichen reservieren.',
            'canonical'   => url('/kennzeichen/'.$k->slug),
            'robots'      => $noindex ? 'noindex,follow' : 'index,follow',
            'schemas'     => $schemas,
            'kuerzel'     => $k,
            'bundesland'  => $bundesland,
        ]);
    }

    public function bundeslandStellen(string $land)
    {
        $bl = Bundesland::with([
            'zulassungsstellen'                   => fn ($q) => $q->whereNull('parent_id')->orderBy('ort'),
            'zulassungsstellen.bundesland',
            'zulassungsstellen.kennzeichenKuerzel',
        ])->where('slug', $land)->firstOrFail();

        // Kürzel des Landes (entdoppelt) + passende Ratgeber.
        $kuerzel = $bl->zulassungsstellen
            ->flatMap->kennzeichenKuerzel->unique('id')->sortBy('code')->values();
        $artikel = RatgeberArtikel::whereNotNull('published_at')
            ->orderByDesc('published_at')->limit(4)->get();

        // Übersichtlich nach Anfangsbuchstabe des Ortes gruppieren.
        $gruppen = $bl->zulassungsstellen
            ->groupBy(fn ($s) => Str::upper(Str::substr($s->ort ?: $s->name, 0, 1)))
            ->sortKeys();

        $items = [];
        foreach ($bl->zulassungsstellen as $i => $s) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $s->name,
                'url'      => $s->url(),
            ];
        }

        $schemas = [
            ['@context' => 'https://schema.org', '@type' => 'ItemList', 'itemListElement' => $items],
            $this->breadcrumb([
                ['Start', url('/')],
                ['Zulassungsstellen', url('/zulassungsstelle')],
                [$bl->name, url('/zulassungsstelle/'.$bl->slug)],
            ]),
        ];

        return view('pages.bundesland', [
            'title'       => 'Zulassungsstellen in '.$bl->name.' — Übersicht & Kennzeichen',
            'description' => 'Alle Kfz-Zulassungsstellen in '.$bl->name.' mit Anschrift, Öffnungszeiten und Terminvergabe — plus Kennzeichen-Kürzel des Landes.',
            'canonical'   => url('/zulassungsstelle/'.$bl->slug),
            'robots'      => $bl->zulassungsstellen->isEmpty() ? 'noindex,follow' : 'index,follow',
            'schemas'     => $schemas,
            'land'        => $bl,
            'kuerzel'     => $kuerzel,
            'artikel'     => $artikel,
            'gruppen'     => $gruppen,
        ]);
    }

    public function ratgeberIndex()
    {
        $artikel = RatgeberArtikel::with('kategorie')
            ->whereNotNull('published_at')->orderByDesc('published_at')->get();

        $schemas = [$this->breadcrumb([
            ['Start', url('/')],
            ['Ratgeber', url('/ratgeber')],
        ])];

        return view('pages.ratgeber-index', [
            'title'       => 'Ratgeber — Auto anmelden, abmelden, Kennzeichen & Gesetze',
            'description' => 'Ratgeber rund um Kfz-Zulassung: Auto anmelden, abmelden, ummelden, i-Kfz, Wunschkennzeichen und rechtliche Grundlagen.',
            'canonical'   => url('/ratgeber'),
            'schemas'     => $schemas,
            'artikel'     => $artikel,
        ]);
    }

    public function ratgeberShow(string $slug)
    {
        $a = RatgeberArtikel::with(['kategorie', 'tags', 'seoMeta'])
            ->where('slug', $slug)->firstOrFail();

        $schemas = [
            array_filter([
                '@context'      => 'https://schema.org',
                '@type'         => 'Article',
                'headline'      => $a->titel,
                'description'   => $a->intro,
                'datePublished' => $a->published_at?->toIso8601String(),
                'dateModified'  => $a->updated_at?->toIso8601String(),
                'author'        => ['@type' => 'Organization', 'name' => config('portal.author_name')],
                'publisher'     => ['@type' => 'Organization', 'name' => config('portal.site_name')],
            ]),
            $this->breadcrumb([
                ['Start', url('/')],
                ['Ratgeber', url('/ratgeber')],
                [$a->titel, url('/ratgeber/'.$a->slug)],
            ]),
        ];

        // FAQPage-Schema aus den sichtbaren „box-frage"-FAQ-Boxen des Artikels.
        if ($faq = $this->faqSchema((string) $a->body)) {
            $schemas[] = $faq;
        }

        return view('pages.ratgeber-show', [
            'title'       => $a->seoMeta?->title ?? ($a->titel.' — Ratgeber'),
            'description' => $a->seoMeta?->description ?? (string) $a->intro,
            'canonical'   => $a->seoMeta?->canonical ?? url('/ratgeber/'.$a->slug),
            'robots'      => $a->seoMeta?->noindex ? 'noindex,follow' : 'index,follow',
            'ogType'      => 'article',
            'schemas'     => $schemas,
            'artikel'     => $a,
        ]);
    }

    public function ueberUns()
    {
        return view('pages.ueber-uns', [
            'title'       => 'Über uns — '.config('portal.site_name'),
            'description' => 'Wer hinter dem Portal steht, woher die Daten stammen und wie wir arbeiten.',
            'canonical'   => url('/ueber-uns'),
        ]);
    }

    public function legal(string $page)
    {
        $titel = $page === 'impressum' ? 'Impressum' : 'Datenschutzerklärung';

        $file = base_path('../../recht/'.$page.'.md');
        $html = is_file($file) ? Str::markdown((string) file_get_contents($file)) : null;

        return view('pages.legal', [
            'title'     => $titel.' — '.config('portal.site_name'),
            'robots'    => 'noindex,follow',   // Entwurf — nach anwaltlicher Prüfung auf index
            'canonical' => url('/'.$page),
            'heading'   => $titel,
            'html'      => $html,
        ]);
    }

    /** Baut ein BreadcrumbList-Schema aus [[name, url], ...]. */
    private function breadcrumb(array $items): array
    {
        $elements = [];
        foreach ($items as $i => [$name, $url]) {
            $elements[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $name,
                'item'     => $url,
            ];
        }
        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $elements];
    }

    /**
     * Baut ein FAQPage-Schema aus den „box-frage"-Boxen des Artikels.
     * Erwartet Blöcke der Form <div class="box box-frage"><strong>Frage</strong> Antwort</div>.
     * Gibt nur dann ein Schema zurück, wenn mindestens zwei valide FAQs vorliegen.
     */
    private function faqSchema(string $body): ?array
    {
        if (! preg_match_all('~<div class="box box-frage">(.*?)</div>~s', $body, $m)) {
            return null;
        }

        $faqs = [];
        foreach ($m[1] as $block) {
            if (! preg_match('~<strong>(.*?)</strong>(.*)~s', $block, $qa)) {
                continue;
            }
            $frage   = $this->cleanFaq($qa[1]);
            $antwort = $this->cleanFaq($qa[2]);
            if ($frage === '' || $antwort === '') {
                continue;
            }
            $faqs[] = [
                '@type'          => 'Question',
                'name'           => $frage,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $antwort],
            ];
        }

        if (count($faqs) < 2) {
            return null;
        }

        return ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faqs];
    }

    /** Entfernt HTML, dekodiert Entities und normalisiert Whitespace für FAQ-Texte. */
    private function cleanFaq(string $s): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($s), ENT_QUOTES)));
    }

    /** Baut ein FAQPage-Schema aus [[frage, antwortHtmlOderText], ...]; Texte werden bereinigt. */
    private function faqPage(array $pairs): array
    {
        return [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => array_map(fn ($f) => [
                '@type'          => 'Question',
                'name'           => $this->cleanFaq($f[0]),
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $this->cleanFaq($f[1])],
            ], $pairs),
        ];
    }
}
