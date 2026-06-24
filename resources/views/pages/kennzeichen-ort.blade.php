<x-layout :title="$title" :description="$description" :canonical="$canonical" :robots="$robots" :schemas="$schemas">
    <nav class="breadcrumb">
        <a href="{{ url('/') }}">Start</a> ›
        <a href="{{ url('/kennzeichen') }}">Kennzeichen</a>
        @if($gemeinde->bundesland)› <a href="{{ url('/zulassungsstelle/'.$gemeinde->bundesland->slug) }}">{{ $gemeinde->bundesland->name }}</a>@endif
        › Kennzeichen {{ $gemeinde->name }}
    </nav>

    @php
        $codes = $kuerzel->pluck('code')->implode(', ');
        $reservUrl = config('portal.reservation_url').'?utm_source=portal&utm_medium=cta&utm_campaign=ort&ort='.$gemeinde->slug;
        $primary = $kuerzel->first();
    @endphp

    <section class="hero hero-sm reveal in">
        <h1>Kfz-Kennzeichen {{ $gemeinde->name }}</h1>
        @if($kuerzel->isNotEmpty())
            <p class="lead">Fahrzeuge in {{ $gemeinde->name }}@if($gemeinde->kreis) ({{ $gemeinde->kreis->name }})@endif tragen das
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
        <p>
            @foreach($kuerzel as $k)
                <a class="badge" href="{{ url('/kennzeichen/'.$k->slug) }}">{{ $k->code }}</a>
            @endforeach
        </p>
        @if($gemeinde->kreis)<p class="muted">Zulassungsbezirk: {{ $gemeinde->kreis->name }}@if($gemeinde->bundesland) · {{ $gemeinde->bundesland->name }}@endif</p>@endif
    </section>

    {{-- Wunschkennzeichen-CTA --}}
    <section class="section reveal">
        <div class="pri-cta-block">
            <h2>Wunschkennzeichen @if($primary){{ $primary->code }} @endif in {{ $gemeinde->name }} reservieren</h2>
            <p>Prüfe live, ob deine Wunsch-Kombination frei ist, und sichere sie in wenigen Minuten – bequem online.</p>
            <a class="cta js-reservierung-cta" data-label="ort:{{ $gemeinde->slug }}" href="{{ $reservUrl }}" rel="nofollow">Wunschkennzeichen prüfen &amp; reservieren →</a>
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
        <p>
            @foreach($altkennzeichen as $k)
                <a class="badge" href="{{ url('/kennzeichen/'.$k->slug) }}">{{ $k->code }}</a>
            @endforeach
        </p>
    </section>
    @endif

    {{-- Nachbarorte im Kreis --}}
    @if($nachbarn->isNotEmpty())
    <section class="section reveal">
        <h2>Kennzeichen in der Umgebung @if($gemeinde->kreis)({{ $gemeinde->kreis->name }})@endif</h2>
        <div class="grid">
            @foreach($nachbarn as $n)
                <div class="card"><a href="{{ url('/kennzeichen/ort/'.$n->slug) }}">Kennzeichen {{ $n->name }}</a></div>
            @endforeach
        </div>
    </section>
    @endif

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
            <div class="card"><a href="{{ url('/ratgeber/wunschkennzeichen-reservieren') }}">Wunschkennzeichen reservieren – so geht's</a></div>
            <div class="card"><a href="{{ url('/ratgeber/auto-anmelden') }}">Auto anmelden – Schritt für Schritt</a></div>
            <div class="card"><a href="{{ url('/ratgeber/i-kfz-online-zulassung') }}">i-Kfz – online zulassen</a></div>
        </div>
    </section>

    <x-ad-slot position="ort_unten" />
</x-layout>
