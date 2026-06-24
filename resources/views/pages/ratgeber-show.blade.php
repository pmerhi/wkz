<x-layout :title="$title" :description="$description" :canonical="$canonical" :robots="$robots" :ogType="$ogType" :schemas="$schemas">
    <nav class="breadcrumb">
        <a href="{{ url('/') }}">Start</a> ›
        <a href="{{ url('/ratgeber') }}">Ratgeber</a> › {{ $artikel->titel }}
    </nav>

    <section class="hero hero-sm reveal in">
        @if($artikel->kategorie)<p class="badge" style="background:rgba(255,255,255,.18);border-color:rgba(255,255,255,.35);color:#fff">{{ $artikel->kategorie->name }}</p>@endif
        <h1>{{ $artikel->titel }}</h1>
        @if($artikel->intro)<p class="lead">{{ $artikel->intro }}</p>@endif
    </section>

    <article class="content wrap--narrow reveal">
        {!! \Illuminate\Support\Str::markdown($artikel->body ?? '') !!}
    </article>

    @if($artikel->tags->isNotEmpty())
        <p>
            @foreach($artikel->tags as $t)
                <span class="badge">{{ $t->name }}</span>
            @endforeach
        </p>
    @endif

    @if($artikel->stand_datum || $artikel->quelle)
        <p class="muted">
            @if($artikel->stand_datum)Rechtsstand: {{ $artikel->stand_datum->format('d.m.Y') }}@endif
            @if($artikel->quelle) · Quelle: {{ $artikel->quelle }}@endif
        </p>
    @endif

    {{-- Verwandte Ratgeber (interne Verlinkung in die Themen-Cluster) --}}
    @if($verwandte->isNotEmpty())
    <section class="section reveal">
        <h2>Verwandte Ratgeber</h2>
        <div class="grid">
            @foreach($verwandte as $v)
                <div class="card reveal {{ 'reveal-d'.($loop->index % 3 + 1) }}">
                    @if($v->kategorie)<div class="muted" style="font-size:.74rem;text-transform:uppercase;letter-spacing:.04em">{{ $v->kategorie->name }}</div>@endif
                    <a href="{{ url('/ratgeber/'.$v->slug) }}">{{ $v->titel }}</a>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Verzeichnis-Hub: verteilt Link-Equity aus dem Ratgeber in die Geo-/Kürzel-Seiten --}}
    <section class="section reveal">
        <h2>Im Verzeichnis weiterstöbern</h2>
        <div class="grid">
            <div class="card">
                <a href="{{ url('/zulassungsstelle') }}"><strong>Zulassungsstellen nach Bundesland</strong></a>
                <div class="muted">Adresse, Öffnungszeiten und Online-Termin deiner zuständigen Zulassungsstelle finden.</div>
            </div>
            <div class="card">
                <a href="{{ url('/kennzeichen') }}"><strong>Kfz-Kennzeichen A–Z</strong></a>
                <div class="muted">Alle Unterscheidungszeichen mit Stadt/Landkreis – von A wie Aachen bis Z.</div>
            </div>
            <div class="card">
                <a href="{{ url('/altkennzeichen') }}"><strong>Altkennzeichen</strong></a>
                <div class="muted">Wieder eingeführte Kennzeichen deiner Heimatregion als Wunschkennzeichen sichern.</div>
            </div>
        </div>
    </section>

    <section class="section reveal" style="text-align:center">
        <h2>Weiteren Ratgeber finden</h2>
        <form class="hero-search" method="get" action="{{ url('/ratgeber') }}" role="search" style="margin:14px auto 0" data-suggest="{{ url('/ratgeber/vorschlaege') }}">
            <input type="search" name="q" placeholder="z. B. ummelden, eVB, i-Kfz …" aria-label="Ratgeber durchsuchen">
            <button class="cta" type="submit">Suchen</button>
        </form>
    </section>

    <x-ad-slot position="ratgeber_unten" />
</x-layout>
