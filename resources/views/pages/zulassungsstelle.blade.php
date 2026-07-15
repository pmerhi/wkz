@php
    // Seiten-internes Menü – nur Sprungmarken auf dieser Seite (Besucher bleibt hier).
    // Einzige Quelle für Kopf-Menü UND jumpnav, damit beide nicht auseinanderlaufen.
    $hatHoursKopf = is_array($stelle->oeffnungszeiten) && count($stelle->oeffnungszeiten) && ! isset($stelle->oeffnungszeiten['raw']);
    $kopfNav = [];
    $kopfNav[] = ['href' => '#kontakt', 'label' => 'Kontakt'];
    if ($hatHoursKopf) $kopfNav[] = ['href' => '#oeffnungszeiten', 'label' => 'Öffnungszeiten'];
    $kopfNav[] = ['href' => '#online', 'label' => 'Online-Zulassung'];
    $kopfNav[] = ['href' => '#reservieren', 'label' => 'Wunschkennzeichen'];
    if ($stelle->termin_url) $kopfNav[] = ['href' => '#termin', 'label' => 'Termin'];
    $kopfNav[] = ['href' => '#formulare', 'label' => 'Formulare'];
    $kopfNav[] = ['href' => '#mitbringen', 'label' => 'Was mitbringen'];
    $kopfNav[] = ['href' => '#faq', 'label' => 'FAQ'];
@endphp
<x-layout :title="$title" :description="$description" :canonical="$canonical" :robots="$robots" :schemas="$schemas" :brand="$stelle->kopf_titel ?: $stelle->anzeigeName()" :nav-links="$kopfNav">
    {{-- Breadcrumb hier bewusst ausgeblendet: Besucher soll auf der Seite bleiben.
         BreadcrumbList-JSON-LD bleibt für SEO erhalten (kommt aus dem Controller). --}}

    @php
        $ortLabel = $stelle->ort ?: $stelle->name;
        $hatHours = is_array($stelle->oeffnungszeiten) && count($stelle->oeffnungszeiten) && ! isset($stelle->oeffnungszeiten['raw']);
        $hatRawHours = is_array($stelle->oeffnungszeiten) && isset($stelle->oeffnungszeiten['raw']);
        $kuerzel = $stelle->kennzeichenKuerzel->first();
        $istOsm = str_contains((string) $stelle->quelle, 'OpenStreetMap');
        $kuerzelHinweis = $kuerzel ? ' (Unterscheidungszeichen <strong>'.e($kuerzel->code).'</strong>)' : '';
    @endphp

    {{-- Titel (H1) und Sprungnavigation stehen ausschließlich im Header oben,
         um Dopplungen zu vermeiden. --}}
    <style>
        .intro-with-badges{display:flex;align-items:center;gap:28px;justify-content:space-between}
        .intro-with-badges .lead-intro{margin:0}
        .intro-badges{display:flex;align-items:center;gap:16px;flex:0 0 auto}
        .intro-badges img{height:66px;width:auto;display:block}
        @media(max-width:640px){
            .intro-with-badges{flex-direction:column;align-items:flex-start;gap:14px}
            .intro-badges img{height:52px}
        }
    </style>
    <div class="intro-with-badges">
        <p class="lead-intro">Die <strong>{{ $stelle->anzeigeName() }}</strong> ist für die Kfz-Zulassung in
            <strong>{{ $ortLabel }}</strong> zuständig{!! $kuerzelHinweis !!}.
            Hier findest du heutige Öffnungszeiten, Online-Termin und Kontakt – und kannst dein
            Wunschkennzeichen direkt reservieren.</p>
        <div class="intro-badges">
            <img src="{{ asset('img/ssl.svg') }}" alt="SSL-Zertifikat" width="66" height="66" loading="lazy">
            <img src="{{ asset('img/din.svg') }}" alt="DIN geprüft" width="66" height="66" loading="lazy">
        </div>
    </div>

    {{-- Kontakt & Anschrift (ganz oben) --}}
    <section class="section reveal" id="kontakt">
        <h2>Kontakt &amp; Anschrift</h2>
        <table class="info">
            <tr><th>Anschrift</th><td>{{ $stelle->strasse }}@if($stelle->strasse)<br>@endif{{ $stelle->plz }} {{ $stelle->ort }}</td></tr>
            @if($stelle->telefon)<tr><th>Telefon</th><td>{{ $stelle->telefon }}</td></tr>@endif
            @if($stelle->email)<tr><th>E-Mail</th><td><a href="mailto:{{ $stelle->email }}">{{ $stelle->email }}</a></td></tr>@endif
            @if($stelle->website)<tr><th>Website</th><td><a href="{{ $stelle->website }}" rel="nofollow noopener" target="_blank">{{ $stelle->website }}</a></td></tr>@endif
            @if($stelle->bundesland)<tr><th>Bundesland</th><td><a href="{{ url('/zulassungsstelle/'.$stelle->bundesland->slug) }}">{{ $stelle->bundesland->name }}</a></td></tr>@endif
        </table>

        {{-- Standortkarte (basemap.de) – nur wenn Koordinaten vorliegen --}}
        <x-standort-karte :stelle="$stelle" />
    </section>

    {{-- Reservierungsmaske direkt unter der Karte (mit Ortskürzel der Stelle) --}}
    <x-kennzeichen-generator :kuerzel="$kuerzel?->code" />

    {{-- Öffnungszeiten: heute mit Balken, ganze Woche aufklappbar --}}
    @if($hatHours || $hatRawHours)
    <section class="section reveal" id="oeffnungszeiten">
        <h2>Öffnungszeiten {{ $ortLabel }}</h2>
        @if($hatHours)
            <x-oeffnungszeiten :data="$stelle->oeffnungszeiten" />
        @else
            <p>{{ $stelle->oeffnungszeiten['raw'] }}</p>
        @endif
    </section>
    @endif

    {{-- Beste Besuchszeit (Erfahrungswerte) --}}
    <x-besuchszeit :ort="$ortLabel" :hatTermin="(bool) $stelle->termin_url" />

    {{-- Online-Zulassung (i-Kfz) – der große „Neu"-Bereich --}}
    <section class="section reveal" id="online">
        <div class="feature">
            <span class="tag-new">Neu · i-Kfz Stufe 4</span>
            <h2>Auto online zulassen, ab- &amp; ummelden</h2>
            <p class="lead-intro">Viele Vorgänge gehen heute komplett digital – ganz ohne Gang zur
                Zulassungsstelle {{ $ortLabel }}. Über das bundesweite <a href="{{ url('/kfz-ratgeber/i-kfz-online-zulassung') }}">i-Kfz-Portal</a>
                erledigst du An-, Ab- und Ummeldung rund um die Uhr von zu Hause.</p>
            <div class="grid">
                <div class="card"><strong>✅ Voraussetzungen</strong>
                    <div class="muted">Online-Ausweis (eID) mit PIN, Smartphone/Lesegerät, Sicherheitscodes auf Schein, Brief &amp; Plaketten.</div>
                </div>
                <div class="card"><strong>🚗 Anmelden &amp; ummelden</strong>
                    <div class="muted">Neu- und Gebrauchtwagen online zulassen, Halterwechsel und Umzug digital melden.</div>
                </div>
                <div class="card"><strong>🅿️ Abmelden</strong>
                    <div class="muted">Fahrzeug außer Betrieb setzen – sofort und gebührengünstig online.</div>
                </div>
            </div>
            <p style="margin:18px 0 0"><a class="btn" href="{{ url('/kfz-ratgeber/i-kfz-online-zulassung') }}">So funktioniert i-Kfz →</a></p>
        </div>
    </section>

    {{-- Wunschkennzeichen reservieren --}}
    <section class="section reveal" id="reservieren">
        <div class="pri-cta-block">
            <h2>Wunschkennzeichen @if($kuerzel){{ $kuerzel->code }} @endif in {{ $ortLabel }} reservieren</h2>
            <p>Prüfe live, ob deine Wunsch-Kombination frei ist, und sichere sie in wenigen Minuten –
                bequem online, bevor du zur Zulassung gehst.</p>
            <x-reservierung-cta :label="'zst:'.$stelle->slug" campaign="zst" />
        </div>
    </section>

    {{-- Termin --}}
    @if($stelle->termin_url)
    <section class="section reveal" id="termin">
        <h2>Termin bei der Zulassungsstelle {{ $ortLabel }}</h2>
        <p class="lead-intro">Für den Besuch vor Ort empfehlen wir eine Online-Terminbuchung – das spart
            Wartezeit. Die Buchung läuft direkt über das Terminsystem der Behörde.</p>
        <p><a class="btn js-termin" data-label="{{ $stelle->slug }}" href="{{ $stelle->termin_url }}" rel="nofollow noopener" target="_blank">📅 Online-Termin buchen →</a></p>
    </section>
    @endif

    {{-- Formulare zum Download --}}
    <section class="section reveal" id="formulare">
        <h2>Formulare für die Zulassungsstelle {{ $ortLabel }}</h2>
        <div class="box box-info">
            <strong>Für die Online-Zulassung brauchst du keine Formulare.</strong>
            Die Muster helfen nur, wenn du persönlich zur {{ $stelle->name }} gehst.
            <a href="{{ url('/kfz-ratgeber/i-kfz-online-zulassung') }}">Zur Online-Zulassung (i-Kfz) →</a>
        </div>
        <div class="grid">
            @foreach(config('formulare', []) as $slug => $form)
                <div class="card card-dl">
                    <strong>{{ $form['titel'] }}</strong>
                    <div class="card-desc">{{ $form['beschreibung'] }}</div>
                    <a class="btn-dl" href="{{ url('/formulare/'.$slug.'.pdf') }}">⬇ Herunterladen (PDF)</a>
                </div>
            @endforeach
        </div>
        <p class="muted" style="font-size:.82rem;margin-top:12px">Kostenlose Muster ohne Gewähr – kein amtliches
            Dokument. <a href="{{ url('/formulare') }}">Alle Formulare ansehen →</a></p>
    </section>

    {{-- Was mitbringen – Unterlagen-Checkliste je Anliegen --}}
    <x-mitbringen-checkliste :ort="$ortLabel" />

    {{-- Weitere Zulassungsstellen --}}
    @if($stelle->kinder->isNotEmpty())
    <section class="section reveal" id="weitere">
        <h2>Weitere Zulassungsstellen in {{ $stelle->ort }}</h2>
        <div class="grid">
            @foreach($stelle->kinder as $k)
                <div class="card">
                    <strong>{{ $k->name }}</strong>
                    <div class="muted">{{ $k->strasse }}@if($k->strasse)<br>@endif{{ $k->plz }} {{ $k->ort }}</div>
                    @if($k->telefon)<div class="muted">Tel.: {{ $k->telefon }}</div>@endif
                    @if($k->termin_url)<a class="js-termin" data-label="{{ $k->slug }}" href="{{ $k->termin_url }}" rel="nofollow noopener" target="_blank">Online-Termin →</a>@endif
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Städte & Gemeinden im Zulassungsbezirk (reziproke Verlinkung in die Ort-Seiten) --}}
    @if(($gemeinden ?? collect())->isNotEmpty())
    <section class="section reveal">
        <h2>Städte &amp; Gemeinden im Zulassungsbezirk</h2>
        <p class="lead-intro">Welches Kennzeichen gilt in deinem Ort? Hier findest du das Unterscheidungszeichen
            und die Wunschkennzeichen-Reservierung für jede Gemeinde:</p>
        <div class="grid">
            @foreach($gemeinden as $gem)
                <div class="card"><a href="{{ url('/wunschkennzeichen/'.$gem->slug) }}">Kennzeichen {{ $gem->name }}</a></div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Daten & Fakten zur Region (nur wenn gequellte Daten vorliegen) --}}
    <x-region-fakten :kreis="$stelle->kreis" />

    {{-- Kennzeichen-Kürzel --}}
    @if($stelle->kennzeichenKuerzel->isNotEmpty())
    <section class="section reveal">
        <h2>Kennzeichen-Kürzel im Zulassungsbezirk</h2>
        <div class="kzs-liste">
            @foreach($stelle->kennzeichenKuerzel as $k)
                <x-kennzeichen-schild :code="$k->code" :href="url('/kennzeichen/'.$k->slug)" />
            @endforeach
        </div>
    </section>
    @endif

    {{-- FAQ – Inhalt aus dem Controller ($faq), identisch zum FAQPage-Schema --}}
    <section class="section reveal faq" id="faq">
        <h2>Häufige Fragen</h2>
        @foreach($faq as [$frage, $antwort])
            <details>
                <summary>{{ $frage }}</summary>
                <p>{!! $antwort !!}</p>
            </details>
        @endforeach
    </section>

    @if($artikel->isNotEmpty())
    <section class="section reveal">
        <h2>Ratgeber rund um die Zulassung in {{ $ortLabel }}</h2>
        <p class="lead-intro">Alles, was du vor dem Termin bei der {{ $stelle->name }} wissen
            solltest – verständlich erklärt:</p>
        <div class="grid">
            @foreach($artikel as $a)
                <div class="card">
                    @if($a->kategorie)<div class="muted" style="font-size:.74rem;text-transform:uppercase;letter-spacing:.04em">{{ $a->kategorie->name }}</div>@endif
                    <a href="{{ url('/kfz-ratgeber/'.$a->slug) }}">{{ $a->titel }}</a>
                </div>
            @endforeach
        </div>
        <p style="margin-top:14px"><a href="{{ url('/kfz-ratgeber') }}">Alle Ratgeber ansehen →</a></p>
    </section>
    @endif

    @if($istOsm)
    <p class="muted" style="font-size:.85rem;margin-top:24px">
        Stammdaten © OpenStreetMap-Mitwirkende, <a href="https://opendatacommons.org/licenses/odbl/" rel="nofollow noopener" target="_blank">ODbL</a>
    </p>
    @endif

    <x-ad-slot position="zst_unten" />
</x-layout>
