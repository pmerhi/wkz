<x-layout :title="$title" :description="$description" :canonical="$canonical" :robots="$robots" :schemas="$schemas">
    <nav class="breadcrumb">
        <a href="{{ url('/') }}">Start</a> ›
        <a href="{{ url('/zulassungsstelle') }}">Zulassungsstellen</a>
        @if($stelle->bundesland)› <a href="{{ url('/zulassungsstelle/'.$stelle->bundesland->slug) }}">{{ $stelle->bundesland->name }}</a>@endif
        › {{ $stelle->ort ?: $stelle->name }}
    </nav>

    @php
        $v = $ab['cta_text'] ?? 'a';
        $ortLabel = $stelle->ort ?: $stelle->name;
        $hatHours = is_array($stelle->oeffnungszeiten) && count($stelle->oeffnungszeiten) && ! isset($stelle->oeffnungszeiten['raw']);
        $hatRawHours = is_array($stelle->oeffnungszeiten) && isset($stelle->oeffnungszeiten['raw']);
        $kuerzel = $stelle->kennzeichenKuerzel->first();
        $reservUrl = config('portal.reservation_url').'?utm_source=portal&utm_medium=cta&utm_campaign=zst&zst='.$stelle->slug;
        $istOsm = str_contains((string) $stelle->quelle, 'OpenStreetMap');
        $kuerzelHinweis = $kuerzel ? ' (Unterscheidungszeichen <strong>'.e($kuerzel->code).'</strong>)' : '';
    @endphp

    <h1>{{ $stelle->name }}</h1>
    <p class="lead-intro">Die <strong>{{ $stelle->name }}</strong> ist für die Kfz-Zulassung in
        <strong>{{ $ortLabel }}</strong> zuständig{!! $kuerzelHinweis !!}.
        Hier findest du heutige Öffnungszeiten, Online-Termin und Kontakt – und kannst dein
        Wunschkennzeichen direkt reservieren.</p>

    <nav class="jumpnav">
        @if($hatHours)<a href="#oeffnungszeiten">🕒 Öffnungszeiten</a>@endif
        <a href="#online">🚗 Online-Zulassung</a>
        <a href="#reservieren">⭐ Wunschkennzeichen</a>
        @if($stelle->termin_url)<a href="#termin">📅 Termin</a>@endif
        <a href="#kontakt">📍 Kontakt</a>
        <a href="#faq">❓ FAQ</a>
    </nav>

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

    {{-- Online-Zulassung (i-Kfz) – der große „Neu"-Bereich --}}
    <section class="section reveal" id="online">
        <div class="feature">
            <span class="tag-new">Neu · i-Kfz Stufe 4</span>
            <h2>Auto online zulassen, ab- &amp; ummelden</h2>
            <p class="lead-intro">Viele Vorgänge gehen heute komplett digital – ganz ohne Gang zur
                Zulassungsstelle {{ $ortLabel }}. Über das bundesweite <a href="{{ url('/ratgeber/i-kfz-online-zulassung') }}">i-Kfz-Portal</a>
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
            <p style="margin:18px 0 0"><a class="btn" href="{{ url('/ratgeber/i-kfz-online-zulassung') }}">So funktioniert i-Kfz →</a></p>
        </div>
    </section>

    {{-- Wunschkennzeichen reservieren --}}
    <section class="section reveal" id="reservieren">
        <div class="pri-cta-block">
            <h2>Wunschkennzeichen @if($kuerzel){{ $kuerzel->code }} @endif in {{ $ortLabel }} reservieren</h2>
            <p>Prüfe live, ob deine Wunsch-Kombination frei ist, und sichere sie in wenigen Minuten –
                bequem online, bevor du zur Zulassung gehst.</p>
            <a class="cta js-reservierung-cta" data-label="zst:{{ $stelle->slug }}" data-variant="{{ $v }}" href="{{ $reservUrl }}" rel="nofollow">{{ $v === 'b' ? 'Jetzt in 2 Minuten sichern →' : 'Wunschkennzeichen prüfen &amp; reservieren →' }}</a>
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

    {{-- Kontakt & Anschrift --}}
    <section class="section reveal" id="kontakt">
        <h2>Kontakt &amp; Anschrift</h2>
        <table class="info">
            <tr><th>Anschrift</th><td>{{ $stelle->strasse }}@if($stelle->strasse)<br>@endif{{ $stelle->plz }} {{ $stelle->ort }}</td></tr>
            @if($stelle->telefon)<tr><th>Telefon</th><td>{{ $stelle->telefon }}</td></tr>@endif
            @if($stelle->email)<tr><th>E-Mail</th><td><a href="mailto:{{ $stelle->email }}">{{ $stelle->email }}</a></td></tr>@endif
            @if($stelle->website)<tr><th>Website</th><td><a href="{{ $stelle->website }}" rel="nofollow noopener" target="_blank">{{ $stelle->website }}</a></td></tr>@endif
            @if($stelle->bundesland)<tr><th>Bundesland</th><td><a href="{{ url('/zulassungsstelle/'.$stelle->bundesland->slug) }}">{{ $stelle->bundesland->name }}</a></td></tr>@endif
        </table>
    </section>

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
                <div class="card"><a href="{{ url('/kennzeichen/ort/'.$gem->slug) }}">Kennzeichen {{ $gem->name }}</a></div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Kennzeichen-Kürzel --}}
    @if($stelle->kennzeichenKuerzel->isNotEmpty())
    <section class="section reveal">
        <h2>Kennzeichen-Kürzel im Zulassungsbezirk</h2>
        <p>
            @foreach($stelle->kennzeichenKuerzel as $k)
                <a class="badge" href="{{ url('/kennzeichen/'.$k->slug) }}">{{ $k->code }}</a>
            @endforeach
        </p>
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
                    <a href="{{ url('/ratgeber/'.$a->slug) }}">{{ $a->titel }}</a>
                </div>
            @endforeach
        </div>
        <p style="margin-top:14px"><a href="{{ url('/ratgeber') }}">Alle Ratgeber ansehen →</a></p>
    </section>
    @endif

    <p class="muted" style="font-size:.85rem;margin-top:24px">
        @if($stelle->last_imported_at)Datenstand: {{ $stelle->last_imported_at->format('d.m.Y') }}@if($stelle->quelle) · Quelle: {{ $stelle->quelle }}@endif@endif
        @if($istOsm) · Stammdaten © OpenStreetMap-Mitwirkende, <a href="https://opendatacommons.org/licenses/odbl/" rel="nofollow noopener" target="_blank">ODbL</a>@endif
    </p>

    <x-ad-slot position="zst_unten" />
</x-layout>
