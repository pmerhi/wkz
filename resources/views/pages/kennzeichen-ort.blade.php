<x-layout :title="$title" :description="$description" :canonical="$canonical" :robots="$robots" :schemas="$schemas">
    <nav class="breadcrumb">
        <a href="{{ url('/') }}">Start</a> ›
        <a href="{{ url('/kennzeichen') }}">Kennzeichen</a>
        @if($gemeinde->bundesland)› <a href="{{ url('/zulassungsstelle/'.$gemeinde->bundesland->slug) }}">{{ $gemeinde->bundesland->name }}</a>@endif
        › Kennzeichen {{ $gemeinde->name }}
    </nav>

    @php
        $codes = $kuerzel->pluck('code')->implode(', ');
        $primary = $kuerzel->first();
    @endphp

    <section class="hero hero-sm reveal in">
        <h1>Kfz-Kennzeichen {{ $gemeinde->name }}</h1>
        @if($kuerzel->isNotEmpty())
            <p class="lead">Fahrzeuge in {{ $gemeinde->name }} tragen das
            Unterscheidungszeichen <strong>{{ $codes }}</strong>. Sichere dir deine Wunsch-Kombination online.</p>
        @else
            <p class="lead">Alles zur Kfz-Zulassung in {{ $gemeinde->name }} – zuständige Zulassungsstelle und
            Wunschkennzeichen reservieren.</p>
        @endif
    </section>

    {{-- Kürzel --}}
    @if($kuerzel->isNotEmpty())
    <section class="section reveal">
        <h2>Unterscheidungszeichen für {{ $gemeinde->name }}</h2>
        <div class="kzs-liste">
            @foreach($kuerzel as $k)
                <x-kennzeichen-schild :code="$k->code" :href="url('/kennzeichen/'.$k->slug)" />
            @endforeach
        </div>
        @php
            $bezirkOrt = $gemeinde->kreis?->name ?: ($stelle && $stelle->ort ? $stelle->ort : '');
            $bezirkLand = $gemeinde->bundesland?->name ?? '';
            $bezirk = trim($bezirkOrt.($bezirkOrt && $bezirkLand ? ' · ' : '').$bezirkLand);
        @endphp
        @if($bezirk !== '')
            <p class="muted">Zulassungsbezirk: {{ $bezirk }}</p>
        @endif
    </section>

    {{-- Interaktiver Wunschkennzeichen-Generator --}}
    @if($primary)<x-kennzeichen-generator :kuerzel="$primary->code" />@endif

    {{-- Daten & Fakten zur Region (nur wenn gequellte Daten vorliegen) --}}
    <x-region-fakten :kreis="$gemeinde->kreis" />

    {{-- Wunschkennzeichen-CTA --}}
    <section class="section reveal">
        <div class="pri-cta-block">
            <h2>Wunschkennzeichen @if($primary){{ $primary->code }} @endif in {{ $gemeinde->name }} reservieren</h2>
            <p>Prüfe live, ob deine Wunsch-Kombination frei ist, und sichere sie in wenigen Minuten – bequem online.</p>
            <x-reservierung-cta :label="'ort:'.$gemeinde->slug" campaign="ort" />
        </div>
    </section>
    @endif

    {{-- Zuständige Zulassungsstelle --}}
    @if($stelle)
    <section class="section reveal">
        <h2>Zuständige Zulassungsstelle für {{ $gemeinde->name }}</h2>
        <div class="card">
            <a href="{{ $stelle->url() }}"><strong>{{ $stelle->name }}</strong></a>
            <div class="muted">{{ $stelle->strasse }}@if($stelle->strasse)<br>@endif{{ $stelle->plz }} {{ $stelle->ort }}</div>
            @if($stelle->termin_url)<a class="js-termin" data-label="{{ $stelle->slug }}" href="{{ $stelle->termin_url }}" rel="nofollow noopener" target="_blank">Online-Termin →</a>@endif
        </div>
        <p style="margin-top:10px"><a href="{{ $stelle->url() }}">Öffnungszeiten, Termin &amp; Kontakt der Zulassungsstelle {{ $stelle->ort ?: $stelle->name }} →</a></p>
    </section>
    @endif

    {{-- Altkennzeichen der Region --}}
    @if($altkennzeichen->isNotEmpty())
    <section class="section reveal">
        <h2>Altkennzeichen rund um {{ $gemeinde->name }}</h2>
        <p>Für die Region ist auch ein wieder eingeführtes <a href="{{ url('/altkennzeichen') }}">Altkennzeichen</a> erhältlich:</p>
        <div class="kzs-liste">
            @foreach($altkennzeichen as $k)
                <x-kennzeichen-schild :code="$k->code" :href="url('/kennzeichen/'.$k->slug)" />
            @endforeach
        </div>
    </section>
    @endif

    {{-- Nachbarorte im Kreis --}}
    @if($nachbarn->isNotEmpty())
    <section class="section reveal">
        <h2>Kennzeichen in der Umgebung</h2>
        <div class="grid">
            @foreach($nachbarn as $n)
                <div class="card"><a href="{{ url('/wunschkennzeichen/'.$n->slug) }}">Kennzeichen {{ $n->name }}</a></div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Wusstest du? (freigegebene Fakten) --}}
    <x-wusstest-box />

    {{-- FAQ --}}
    @if(count($faq) >= 1)
    <section class="section reveal faq" id="faq">
        <h2>Häufige Fragen</h2>
        @foreach($faq as [$frage, $antwort])
            <details>
                <summary>{{ $frage }}</summary>
                <p>{{ $antwort }}</p>
            </details>
        @endforeach
    </section>
    @endif

    {{-- Ratgeber-Verlinkung --}}
    <section class="section reveal">
        <h2>Passende Ratgeber</h2>
        <div class="grid">
            <div class="card"><a href="{{ url('/kfz-ratgeber/wunschkennzeichen-reservieren') }}">Wunschkennzeichen reservieren – so geht's</a></div>
            <div class="card"><a href="{{ url('/kfz-ratgeber/auto-anmelden') }}">Auto anmelden – Schritt für Schritt</a></div>
            <div class="card"><a href="{{ url('/kfz-ratgeber/i-kfz-online-zulassung') }}">i-Kfz – online zulassen</a></div>
        </div>
    </section>

    <x-quiz-teaser :code="$primary?->code" />

    <x-ad-slot position="ort_unten" />
</x-layout>
