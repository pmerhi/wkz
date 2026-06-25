<?php

namespace App\Http\Controllers;

use App\Models\Bundesland;
use App\Models\Gemeinde;
use App\Models\KennzeichenKuerzel;
use App\Models\QuizScore;
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

        // Gemeinden des Zulassungsbezirks → reziproke Verlinkung in die Ort-Seiten.
        $gemeinden = $stelle->kreis_id
            ? Gemeinde::where('kreis_id', $stelle->kreis_id)->whereNotNull('slug')
                ->orderBy('name')->limit(60)->get(['id', 'name', 'slug'])
            : collect();

        return view('pages.zulassungsstelle', [
            'title'       => $stelle->seoMeta?->title ?? ($stelle->name.' — Öffnungszeiten, Termin & Wunschkennzeichen'),
            'description' => $stelle->seoMeta?->description ?? ('Zulassungsstelle '.$stelle->name.': Anschrift, Öffnungszeiten, Terminvergabe und Wunschkennzeichen reservieren.'),
            'canonical'   => $stelle->seoMeta?->canonical ?? $stelle->url(),
            'robots'      => $noindex ? 'noindex,follow' : 'index,follow',
            'schemas'     => $schemas,
            'stelle'      => $stelle,
            'artikel'     => $artikel,
            'faq'         => $faq,
            'gemeinden'   => $gemeinden,
        ]);
    }

    /** Kennzeichen-Quiz „Kürzel-Raten" – Pool aus den Kürzeln mit Bedeutung (Fragen baut das JS endlos). */
    public function kennzeichenQuiz()
    {
        $clean = fn ($b) => trim(explode(',', (string) $b)[0]);

        // „Großstadt"-Indikator: höchster Kfz-Bestand der mit dem Kürzel verknüpften Kreise.
        $pop = \Illuminate\Support\Facades\DB::table('kennzeichen_kuerzel_kreis as kk')
            ->join('kennzeichen_kuerzel as k', 'k.id', '=', 'kk.kennzeichen_kuerzel_id')
            ->join('kreis_statistik as s', 's.kreis_id', '=', 'kk.kreis_id')
            ->groupBy('k.code')
            ->selectRaw('k.code as code, max(s.kfz_bestand) as pop')
            ->pluck('pop', 'code');

        $pool = KennzeichenKuerzel::whereNotNull('bedeutung')->where('bedeutung', '!=', '')
            ->get(['code', 'bedeutung'])
            ->map(fn ($k) => ['code' => $k->code, 'antwort' => $clean($k->bedeutung)])
            ->filter(fn ($x) => $x['antwort'] !== '')
            ->unique('code')
            // Schwierigkeit: kurz + im Namen enthalten + bevölkerungsreich = leicht.
            ->map(function ($x) use ($pop) {
                $len = mb_strlen($x['code']);
                $score = $len * 100;
                if ($this->codeImNamen($x['code'], $x['antwort'])) {
                    $score -= 30;
                }
                $score -= min(60, (int) ($pop[$x['code']] ?? 0) / 20000);
                $x['s'] = $score;
                return $x;
            })
            ->sortBy('s')                                  // leicht → schwer
            ->map(fn ($x) => ['code' => $x['code'], 'antwort' => $x['antwort']])
            ->values();

        return view('pages.kennzeichen-quiz', [
            'title'       => 'Kennzeichen-Quiz: 3 Leben, 15 Sekunden – schaffst du den Highscore?',
            'description' => 'Das schnelle Kfz-Kennzeichen-Quiz: 3 Leben, 15 Sekunden pro Frage, je schneller desto '
                .'mehr Punkte. Trag dich in die Bestenliste ein – Tages-, Wochen-, Monats- und Jahres-Highscore.',
            'canonical'   => url('/kennzeichen-quiz'),
            'schemas'     => [$this->breadcrumb([
                ['Start', url('/')],
                ['Kennzeichen', url('/kennzeichen')],
                ['Quiz', url('/kennzeichen-quiz')],
            ])],
            'pool'        => $pool,
            'highscores'  => $this->quizListen(),
        ]);
    }

    /** Speichert ein Quiz-Ergebnis und gibt die aktualisierten Bestenlisten zurück. */
    public function quizSpeichern(Request $request)
    {
        $name = trim((string) $request->input('name'));
        $name = $name === '' ? 'Anonym' : Str::limit(strip_tags($name), 40, '');

        QuizScore::create([
            'name'       => $name,
            'score'      => max(0, min(1_000_000, (int) $request->input('score'))),
            'richtige'   => max(0, min(9999, (int) $request->input('richtige'))),
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true, 'highscores' => $this->quizListen()]);
    }

    /** Liefert die Tagesbestenliste als JSON (für die Live-Aktualisierung nach dem Speichern). */
    public function quizHighscores()
    {
        return response()->json($this->quizListen());
    }

    /** Rangliste-Seite: Top 50 je Zeitraum (Heute/Woche/Monat/Insgesamt) mit Datum. */
    public function quizRangliste()
    {
        $zeit = ['tag' => 'Heute', 'woche' => 'Woche', 'monat' => 'Monat', 'gesamt' => 'Insgesamt'];

        $listen = [];
        foreach (array_keys($zeit) as $z) {
            $listen[$z] = QuizScore::topliste($z, 50)->map(fn ($s) => [
                'name'     => $s->name,
                'score'    => $s->score,
                'richtige' => $s->richtige,
                'datum'    => $s->created_at?->format('d.m.Y H:i'),
            ])->values();
        }

        return view('pages.quiz-rangliste', [
            'title'       => 'Hall of Fame – die besten Kennzeichen-Rater',
            'description' => 'Die Hall of Fame des Kfz-Kennzeichen-Quiz: Top 50 von heute, dieser Woche, '
                .'diesem Monat und insgesamt.',
            'canonical'   => url('/kennzeichen-quiz/hall-of-fame'),
            'robots'      => 'noindex,follow',
            'schemas'     => [$this->breadcrumb([
                ['Start', url('/')],
                ['Kennzeichen', url('/kennzeichen')],
                ['Quiz', url('/kennzeichen-quiz')],
                ['Hall of Fame', url('/kennzeichen-quiz/hall-of-fame')],
            ])],
            'zeit'        => $zeit,
            'listen'      => $listen,
        ]);
    }

    /** Baut die Tagesbestenliste. */
    private function quizListen(): array
    {
        return [
            'tag' => QuizScore::topliste('tag')->map(fn ($s) => [
                'name'     => $s->name,
                'score'    => $s->score,
                'richtige' => $s->richtige,
            ])->values(),
        ];
    }

    /** Prüft, ob die Buchstaben des Kürzels als Teilfolge im Ortsnamen vorkommen (= leichter zu raten). */
    private function codeImNamen(string $code, string $name): bool
    {
        $name = mb_strtoupper($name);
        $code = mb_strtoupper($code);
        $pos = 0;
        $lenN = mb_strlen($name);
        for ($i = 0; $i < mb_strlen($code); $i++) {
            $ch = mb_substr($code, $i, 1);
            $found = false;
            while ($pos < $lenN) {
                if (mb_substr($name, $pos, 1) === $ch) {
                    $found = true;
                    $pos++;
                    break;
                }
                $pos++;
            }
            if (! $found) {
                return false;
            }
        }
        return true;
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

    /** Schränkt eine Gemeinde-Query auf indexierbare Ort-Seiten ein (Kürzel + zuständige Stelle). */
    private function ortIndexierbar($q)
    {
        return $q->whereNotNull('slug')
            ->whereHas('kreis.kennzeichenKuerzel')
            ->where(fn ($x) => $x
                ->whereHas('zulassungsstellen', fn ($s) => $s->whereNull('parent_id'))
                ->orWhereHas('kreis.zulassungsstellen', fn ($s) => $s->whereNull('parent_id')));
    }

    /** Ort-Hub: Übersicht aller Bundesländer mit Anzahl der Ort-Seiten. */
    public function ortHub()
    {
        $counts = $this->ortIndexierbar(Gemeinde::query())
            ->selectRaw('bundesland_id, count(*) as c')->groupBy('bundesland_id')
            ->pluck('c', 'bundesland_id');

        $laender = Bundesland::orderBy('name')->get()
            ->map(function ($b) use ($counts) {
                $b->ort_count = (int) ($counts[$b->id] ?? 0);
                return $b;
            })->filter(fn ($b) => $b->ort_count > 0)->values();

        $gesamt = $counts->sum();

        $schemas = [$this->breadcrumb([
            ['Start', url('/')],
            ['Kennzeichen', url('/kennzeichen')],
            ['Kennzeichen nach Ort', url('/kennzeichen/ort')],
        ])];

        return view('pages.ort-hub', [
            'title'       => 'Kfz-Kennzeichen nach Ort — Verzeichnis aller Städte & Gemeinden',
            'description' => 'Welches Kennzeichen hat dein Ort? Verzeichnis aller deutschen Städte und Gemeinden '
                .'nach Bundesland – mit Unterscheidungszeichen, zuständiger Zulassungsstelle und Wunschkennzeichen.',
            'canonical'   => url('/kennzeichen/ort'),
            'schemas'     => $schemas,
            'laender'     => $laender,
            'gesamt'      => $gesamt,
        ]);
    }

    /** Ort-Hub eines Bundeslands: Orte nach Kreis gruppiert. */
    public function ortHubLand(string $slug)
    {
        $land = Bundesland::where('slug', $slug)->firstOrFail();

        $gemeinden = $this->ortIndexierbar(Gemeinde::query())
            ->where('bundesland_id', $land->id)
            ->with('kreis.kennzeichenKuerzel')
            ->orderBy('name')->get(['id', 'name', 'slug', 'kreis_id']);

        // Kreisnamen fehlen in den Stammdaten → Bezirk über die zuständige Zulassungsstelle (Ort) labeln.
        $stellenOrt = Zulassungsstelle::whereNull('parent_id')
            ->where('bundesland_id', $land->id)->whereNotNull('kreis_id')
            ->get(['ort', 'name', 'kreis_id'])->keyBy('kreis_id');

        // Gruppierung nach Zulassungsbezirk (kreis_id); Label = Kreisname, sonst Stellen-Ort, sonst Kürzel.
        $gruppen = $gemeinden->groupBy('kreis_id')->map(function ($orte) use ($stellenOrt) {
            $kreis   = $orte->first()?->kreis;
            $kuerzel = $kreis?->kennzeichenKuerzel ?? collect();
            $stelle  = $stellenOrt[$orte->first()?->kreis_id] ?? null;
            $label   = $kreis?->name ?: ($stelle?->ort ?: ($kuerzel->pluck('code')->implode(', ') ?: 'Weitere Orte'));

            return [
                'label'   => $label,
                'kuerzel' => $kuerzel->sortBy('code')->values(),
                'orte'    => $orte->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            ];
        })->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)->values();

        $schemas = [$this->breadcrumb([
            ['Start', url('/')],
            ['Kennzeichen', url('/kennzeichen')],
            ['Kennzeichen nach Ort', url('/kennzeichen/ort')],
            [$land->name, url('/kennzeichen/ort/bundesland/'.$land->slug)],
        ])];

        return view('pages.ort-hub-land', [
            'title'       => 'Kfz-Kennzeichen in '.$land->name.' — alle Städte & Gemeinden',
            'description' => 'Alle Städte und Gemeinden in '.$land->name.' mit ihrem Kfz-Kennzeichen, '
                .'zuständiger Zulassungsstelle und Wunschkennzeichen-Reservierung – nach Landkreis geordnet.',
            'canonical'   => url('/kennzeichen/ort/bundesland/'.$land->slug),
            'robots'      => $gemeinden->isEmpty() ? 'noindex,follow' : 'index,follow',
            'schemas'     => $schemas,
            'land'        => $land,
            'gruppen'     => $gruppen,
            'anzahl'      => $gemeinden->count(),
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

    /** Programmatic Ort-Seite: „Welches Kennzeichen hat {Ort}?" inkl. zuständiger Zulassungsstelle. */
    public function kennzeichenOrt(string $slug)
    {
        $g = Gemeinde::with(['kreis.kennzeichenKuerzel', 'bundesland'])
            ->where('slug', $slug)->firstOrFail();

        $kuerzel = $g->kennzeichenKuerzel()->sortBy('code')->values();
        $stelle  = $g->zustaendigeStelle();
        $altkennzeichen = $kuerzel->where('ist_altkennzeichen', true)->values();

        // Nachbar-Orte im selben Kreis (interne Verlinkung, Mehrwert gegen Thin Content).
        $nachbarn = $g->kreis_id
            ? Gemeinde::where('kreis_id', $g->kreis_id)->where('id', '!=', $g->id)
                ->whereNotNull('slug')->orderBy('name')->limit(30)->get(['id', 'name', 'slug'])
            : collect();

        // Indexierbar nur mit echtem Mehrwert: mindestens ein Kürzel UND eine zuständige Stelle.
        $indexable = $kuerzel->isNotEmpty() && $stelle !== null;

        $codes = $kuerzel->pluck('code')->implode(', ');
        $crumbs = [
            ['Start', url('/')],
            ['Kennzeichen', url('/kennzeichen')],
        ];
        if ($g->bundesland) {
            $crumbs[] = [$g->bundesland->name, url('/zulassungsstelle/'.$g->bundesland->slug)];
        }
        $crumbs[] = ['Kennzeichen '.$g->name, $g->url()];

        $faq = [];
        if ($kuerzel->isNotEmpty()) {
            $faq[] = ['Welches Kfz-Kennzeichen hat '.$g->name.'?',
                'Fahrzeuge in '.$g->name.' tragen das Unterscheidungszeichen '.$codes
                .'. Das Kennzeichen lässt sich als Wunschkennzeichen reservieren.'];
        }
        if ($stelle) {
            $faq[] = ['Welche Zulassungsstelle ist für '.$g->name.' zuständig?',
                'Zuständig ist die '.$stelle->name.($stelle->ort ? ' in '.$stelle->ort : '').'. '
                .'Dort meldest du dein Fahrzeug an, ab oder um.'];
        }
        if ($altkennzeichen->isNotEmpty()) {
            $faq[] = ['Gibt es für '.$g->name.' ein Altkennzeichen?',
                'Für die Region ist das wieder eingeführte Altkennzeichen '
                .$altkennzeichen->pluck('code')->implode(', ').' erhältlich.'];
        }

        // Datengetriebene FAQ mit echten Werten (KBA/Destatis) – einzigartig je Region.
        $stat  = $g->kreis?->statistik;
        $regio = $g->kreis?->name ?: $g->name;
        if ($stat && $stat->kfz_bestand) {
            $satz = 'Im Zulassungsbezirk '.$regio.' sind rund '.number_format($stat->kfz_bestand, 0, ',', '.').' Kraftfahrzeuge zugelassen';
            if ($stat->pkw_bestand) {
                $satz .= ', darunter '.number_format($stat->pkw_bestand, 0, ',', '.').' Pkw';
            }
            if ($stat->pkw_dichte) {
                $satz .= ' ('.number_format($stat->pkw_dichte, 0, ',', '.').' Pkw je 1.000 Einwohner)';
            }
            $faq[] = ['Wie viele Autos sind in '.$regio.' zugelassen?', $satz.'.'];
        }
        if ($stat && $stat->elektro_pkw && $stat->pkw_bestand) {
            $faq[] = ['Wie hoch ist der E-Auto-Anteil in '.$regio.'?',
                'Etwa '.number_format($stat->elektro_pkw / $stat->pkw_bestand * 100, 1, ',', '.').' % der Pkw in '
                .$regio.' sind reine Elektroautos ('.number_format($stat->elektro_pkw, 0, ',', '.').' E-Pkw).'];
        }

        $schemas = [$this->breadcrumb($crumbs)];
        if (count($faq) >= 2) {
            $schemas[] = $this->faqPage($faq);
        }

        $titel = $kuerzel->isNotEmpty()
            ? 'Kennzeichen '.$g->name.' ('.$codes.') — Wunschkennzeichen reservieren'
            : 'Kennzeichen '.$g->name.' — Zulassung & Wunschkennzeichen';

        return view('pages.kennzeichen-ort', [
            'title'          => $titel,
            'description'    => 'Welches Kfz-Kennzeichen hat '.$g->name.'?'
                .($codes ? ' Unterscheidungszeichen '.$codes.'.' : '').' Zuständige Zulassungsstelle, '
                .'Öffnungszeiten und Wunschkennzeichen für '.$g->name.' reservieren.',
            'canonical'      => $g->url(),
            'robots'         => $indexable ? 'index,follow' : 'noindex,follow',
            'schemas'        => $schemas,
            'gemeinde'       => $g,
            'kuerzel'        => $kuerzel,
            'altkennzeichen' => $altkennzeichen,
            'stelle'         => $stelle,
            'nachbarn'       => $nachbarn,
            'faq'            => $faq,
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

    public function ratgeberIndex(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $breadcrumb = $this->breadcrumb([
            ['Start', url('/')],
            ['Ratgeber', url('/ratgeber')],
        ]);

        // Suche (?q=) — Ergebnisseite: noindex, Canonical aufs Verzeichnis.
        if ($q !== '') {
            return view('pages.ratgeber-index', [
                'title'       => 'Ratgeber durchsuchen: '.$q,
                'description' => 'Suchergebnisse im Ratgeber rund um Kfz-Zulassung und Wunschkennzeichen.',
                'canonical'   => url('/ratgeber'),
                'robots'      => 'noindex,follow',
                'schemas'     => [$breadcrumb],
                'q'           => $q,
                'treffer'     => $this->sucheRatgeber($q),
                'gruppen'     => null,
                'anzahl'      => RatgeberArtikel::whereNotNull('published_at')->count(),
            ]);
        }

        // Browse-Modus: alle Artikel, nach Kategorie gruppiert.
        $artikel = RatgeberArtikel::with('kategorie')
            ->whereNotNull('published_at')->orderByDesc('published_at')->get();
        $gruppen = $artikel
            ->sortBy(fn ($a) => $a->kategorie?->name ?? 'zzz', SORT_NATURAL | SORT_FLAG_CASE)
            ->groupBy(fn ($a) => $a->kategorie?->name ?? 'Sonstiges');

        return view('pages.ratgeber-index', [
            'title'       => 'Ratgeber — Auto anmelden, abmelden, Kennzeichen & Gesetze',
            'description' => 'Ratgeber rund um Kfz-Zulassung: Auto anmelden, abmelden, ummelden, i-Kfz, Wunschkennzeichen und rechtliche Grundlagen.',
            'canonical'   => url('/ratgeber'),
            'robots'      => 'index,follow',
            'schemas'     => [$breadcrumb],
            'q'           => '',
            'treffer'     => null,
            'gruppen'     => $gruppen,
            'anzahl'      => $artikel->count(),
        ]);
    }

    /** JSON-Endpoint für die Live-Vorschlagsliste (Autocomplete) der Zulassungsstellen-Suche. */
    public function zulassungsstelleSuggest(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        // Wie die Volltextsuche: Primär-Ämter, Treffer in Name oder Ort.
        // Ranking: Ort-Präfix zuerst (Nutzer tippen meist die Stadt), dann Name-Präfix, dann Rest.
        $treffer = Zulassungsstelle::with('bundesland')->whereNull('parent_id')
            ->where(fn ($x) => $x->where('name', 'like', "%{$q}%")->orWhere('ort', 'like', "%{$q}%"))
            ->orderByRaw('CASE WHEN ort LIKE ? THEN 0 WHEN name LIKE ? THEN 1 ELSE 2 END', ["{$q}%", "{$q}%"])
            ->orderBy('ort')->orderBy('name')->limit(7)->get();

        $terms = preg_split('/\s+/u', mb_strtolower($q), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $vorschlaege = $treffer->map(function (Zulassungsstelle $s) use ($terms) {
            $sub = trim(($s->ort ?: '').($s->bundesland ? ' · '.$s->bundesland->name : ''), " ·\t\n");
            return [
                'titel'         => $s->name,
                'titel_html'    => $this->hervorheben($s->name, $terms),
                'kategorie'     => $sub,
                'kategorie_html' => $this->hervorheben($sub, $terms),   // Ort hervorgehoben
                'url'           => $s->url(),
            ];
        })->values();

        return response()->json($vorschlaege);
    }

    /** JSON-Endpoint für die Live-Vorschlagsliste (Autocomplete) der Ratgeber-Suche. */
    public function ratgeberSuggest(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $vorschlaege = $this->sucheRatgeber($q)->take(7)->map(fn (RatgeberArtikel $a) => [
            'titel'      => $a->titel,
            'titel_html' => $a->such_titel_html,   // serverseitig escaped + <mark>
            'kategorie'  => $a->kategorie?->name,
            'url'        => url('/ratgeber/'.$a->slug),
        ])->values();

        return response()->json($vorschlaege);
    }

    /**
     * Ratgeber-Suche über Titel, Intro, Body und Tags.
     * Jeder Suchbegriff muss irgendwo vorkommen (UND); Sortierung nach Relevanz-Score
     * (Titel > Tags > Intro > Body). LIKE-basiert – bei 94 Artikeln vernachlässigbar und
     * substring-tolerant (z. B. „ummeld" findet „ummelden"); funktioniert auch in SQLite-Tests.
     *
     * @return \Illuminate\Support\Collection<int, RatgeberArtikel>
     */
    private function sucheRatgeber(string $q): \Illuminate\Support\Collection
    {
        $terms = collect(preg_split('/\s+/u', mb_strtolower($q), -1, PREG_SPLIT_NO_EMPTY))
            ->filter(fn ($t) => mb_strlen($t) >= 2)->values()->all();
        if (! $terms) {
            return collect();
        }

        // Kandidaten: jeder Begriff muss vorkommen. Primär in Titel/Intro/Tags (= „Artikel handelt
        // davon"), damit die Treffer fokussiert bleiben; nur wenn das nichts liefert, auch im Body.
        $build = fn (bool $mitBody) => RatgeberArtikel::with(['kategorie', 'tags'])
            ->whereNotNull('published_at')
            ->where(function ($outer) use ($terms, $mitBody) {
                foreach ($terms as $t) {
                    $outer->where(function ($x) use ($t, $mitBody) {
                        $x->where('titel', 'like', "%{$t}%")
                          ->orWhere('intro', 'like', "%{$t}%")
                          ->orWhereHas('tags', fn ($qq) => $qq->where('name', 'like', "%{$t}%"));
                        if ($mitBody) {
                            $x->orWhere('body', 'like', "%{$t}%");
                        }
                    });
                }
            });

        $kandidaten = $build(false)->get();
        if ($kandidaten->isEmpty()) {
            $kandidaten = $build(true)->get();
        }

        $phrase = mb_strtolower($q);

        return $kandidaten->map(function (RatgeberArtikel $a) use ($terms, $phrase) {
            $titel = mb_strtolower($a->titel);
            $intro = mb_strtolower((string) $a->intro);
            $bodyT = mb_strtolower(strip_tags((string) $a->body));
            $tags  = mb_strtolower($a->tags->pluck('name')->implode(' '));

            $score = 0;
            foreach ($terms as $t) {
                $score += substr_count($titel, $t) * 10;
                $score += substr_count($tags, $t) * 5;
                $score += substr_count($intro, $t) * 3;
                $score += substr_count($bodyT, $t) * 1;
            }
            if (str_contains($titel, $phrase)) {
                $score += 50;   // exakte Wortgruppe im Titel
            }

            $a->such_score        = $score;
            $a->such_titel_html   = $this->hervorheben($a->titel, $terms);
            $a->such_snippet_html = $this->hervorheben($this->snippet($a, $terms), $terms);

            return $a;
        })->sortByDesc('such_score')->take(50)->values();
    }

    /** Liefert einen Textausschnitt rund um den ersten Treffer (für die Suchergebnis-Karte). */
    private function snippet(RatgeberArtikel $a, array $terms, int $len = 160): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($a->intro.' '.$a->body))));
        $low  = mb_strtolower($text);

        $pos = null;
        foreach ($terms as $t) {
            $p = mb_strpos($low, $t);
            if ($p !== false && ($pos === null || $p < $pos)) {
                $pos = $p;
            }
        }
        if ($pos === null) {
            return rtrim(mb_substr($text, 0, $len)).'…';
        }
        $start = max(0, $pos - 50);
        return ($start > 0 ? '… ' : '').rtrim(mb_substr($text, $start, $len)).'…';
    }

    /** Escaped den Text und hebt die Suchbegriffe mit <mark> hervor (gibt sicheres HTML zurück). */
    private function hervorheben(string $text, array $terms): string
    {
        $safe = e($text);
        foreach ($terms as $t) {
            $safe = preg_replace_callback(
                '/'.preg_quote(e($t), '/').'/iu',
                fn ($m) => '<mark>'.$m[0].'</mark>',
                $safe
            );
        }
        return $safe;
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
            'verwandte'   => $this->verwandteRatgeber($a),
        ]);
    }

    /**
     * Verwandte Ratgeber für die interne Verlinkung: zuerst gleiche Kategorie,
     * danach mit Artikeln aufgefüllt, die sich Tags teilen (jeweils ohne den Artikel selbst).
     *
     * @return \Illuminate\Support\Collection<int, RatgeberArtikel>
     */
    private function verwandteRatgeber(RatgeberArtikel $a, int $limit = 6): \Illuminate\Support\Collection
    {
        $related = collect();
        if ($a->kategorie_id) {
            $related = RatgeberArtikel::with('kategorie')
                ->whereNotNull('published_at')
                ->where('kategorie_id', $a->kategorie_id)
                ->where('id', '!=', $a->id)
                ->orderByDesc('published_at')->limit($limit)->get();
        }

        if ($related->count() < $limit && $a->tags->isNotEmpty()) {
            $more = RatgeberArtikel::with('kategorie')
                ->whereNotNull('published_at')
                ->where('id', '!=', $a->id)
                ->whereNotIn('id', $related->pluck('id'))
                ->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $a->tags->pluck('id')))
                ->orderByDesc('published_at')->limit($limit - $related->count())->get();
            $related = $related->concat($more);
        }

        return $related;
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
