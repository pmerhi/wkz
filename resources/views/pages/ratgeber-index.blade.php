<x-layout :title="$title" :description="$description" :canonical="$canonical" :robots="$robots ?? 'index,follow'" :schemas="$schemas">
    <nav class="breadcrumb"><a href="{{ url('/') }}">Start</a> › Ratgeber</nav>

    @if($q !== '')
        {{-- Suchergebnis-Modus --}}
        <section class="hero hero-sm reveal in">
            <h1>Ratgeber durchsuchen</h1>
            <form class="hero-search" method="get" action="{{ url('/ratgeber') }}" role="search" data-suggest="{{ url('/ratgeber/vorschlaege') }}">
                <input type="search" name="q" value="{{ $q }}" placeholder="z. B. ummelden, eVB, i-Kfz …" aria-label="Ratgeber durchsuchen" autofocus>
                <button class="cta" type="submit">Suchen</button>
            </form>
        </section>

        @if($treffer->isEmpty())
            <p class="muted">Keine Treffer für „{{ $q }}". Versuche es mit einem anderen Begriff –
                oder <a href="{{ url('/ratgeber') }}">stöbere durch alle {{ $anzahl }} Ratgeber →</a></p>
        @else
            <p class="muted">{{ $treffer->count() }} Treffer für „{{ $q }}".</p>
            <div class="grid">
                @foreach($treffer as $a)
                    <div class="card reveal {{ 'reveal-d'.($loop->index % 3 + 1) }}">
                        <a href="{{ url('/ratgeber/'.$a->slug) }}">{!! $a->such_titel_html !!}</a>
                        @if($a->kategorie)<div class="muted" style="font-size:.82rem">{{ $a->kategorie->name }}</div>@endif
                        <div class="muted">{!! $a->such_snippet_html !!}</div>
                    </div>
                @endforeach
            </div>
        @endif
    @else
        {{-- Browse-Modus: nach Kategorie gruppiert --}}
        <section class="hero hero-sm reveal in">
            <h1>Ratgeber rund um die Kfz-Zulassung</h1>
            <p class="lead">Anmelden, abmelden, ummelden, Wunschkennzeichen &amp; Co. – verständlich erklärt,
            Schritt für Schritt.</p>
            <form class="hero-search" method="get" action="{{ url('/ratgeber') }}" role="search" data-suggest="{{ url('/ratgeber/vorschlaege') }}">
                <input type="search" name="q" placeholder="Ratgeber durchsuchen – z. B. ummelden, eVB, i-Kfz …" aria-label="Ratgeber durchsuchen">
                <button class="cta" type="submit">Suchen</button>
            </form>
        </section>

        @if($gruppen->isEmpty())
            <p class="muted">Noch keine Artikel veröffentlicht.</p>
        @else
            <p class="muted">{{ $anzahl }} Ratgeber in {{ $gruppen->count() }} Themenbereichen –
                oder nutze die Suche oben.</p>
            @foreach($gruppen as $kategorie => $artikel)
                <section class="section reveal">
                    <h2>{{ $kategorie }}</h2>
                    <div class="grid">
                        @foreach($artikel as $a)
                            <div class="card reveal {{ 'reveal-d'.($loop->index % 3 + 1) }}">
                                <a href="{{ url('/ratgeber/'.$a->slug) }}">{{ $a->titel }}</a>
                                @if($a->intro)<div class="muted">{{ \Illuminate\Support\Str::limit($a->intro, 120) }}</div>@endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endif
    @endif
</x-layout>
