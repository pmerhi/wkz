<x-layout :title="$title" :description="$description" :canonical="$canonical" :robots="$robots ?? 'index,follow'" :schemas="$schemas">
    <nav class="breadcrumb"><a href="{{ url('/') }}">Start</a> › Zulassungsstellen</nav>

    @if($q !== '')
        {{-- Suchergebnis-Modus --}}
        <section class="hero hero-sm reveal in">
            <h1>Zulassungsstelle suchen: „{{ $q }}"</h1>
            <form class="hero-search" method="get" action="{{ url('/zulassungsstelle') }}" data-suggest="{{ url('/zulassungsstelle/vorschlaege') }}">
                <input type="search" name="q" value="{{ $q }}" placeholder="Stadt oder Behörde …" aria-label="Suche">
                <button class="cta" type="submit">Suchen</button>
            </form>
        </section>
        @if($treffer->isEmpty())
            <p class="muted">Keine Treffer. <a href="{{ url('/zulassungsstelle') }}">Alle Zulassungsstellen nach Bundesland →</a></p>
        @else
            <div class="grid">
                @foreach($treffer as $s)
                    <div class="card reveal {{ 'reveal-d'.($loop->index % 3 + 1) }}">
                        <a href="{{ $s->url() }}">{{ $s->name }}</a>
                        <div class="muted">{{ $s->ort }}@if($s->bundesland) · {{ $s->bundesland->name }}@endif</div>
                    </div>
                @endforeach
            </div>
        @endif
    @else
        {{-- Hub-Modus: nach Bundesland --}}
        <section class="hero hero-sm reveal in">
            <h1>Zulassungsstellen in Deutschland</h1>
            <p class="lead">Adressen, Öffnungszeiten und Online-Terminvergabe – gegliedert nach Bundesland.</p>
            <form class="hero-search" method="get" action="{{ url('/zulassungsstelle') }}" data-suggest="{{ url('/zulassungsstelle/vorschlaege') }}">
                <input type="search" name="q" placeholder="Stadt oder Behörde suchen …" aria-label="Suche">
                <button class="cta" type="submit">Suchen</button>
            </form>
        </section>

        @if($laender->isEmpty())
            <p class="muted">Noch keine Zulassungsstellen erfasst.</p>
        @else
            <p class="muted">Wähle dein Bundesland, um die Zulassungsstellen mit Anschrift,
            Öffnungszeiten und Online-Terminvergabe zu sehen.</p>
            <div class="grid">
                @foreach($laender as $land)
                    <div class="card reveal {{ 'reveal-d'.($loop->index % 3 + 1) }}">
                        <a href="{{ url('/zulassungsstelle/'.$land->slug) }}"><strong>{{ $land->name }}</strong></a>
                        <div class="muted">{{ $land->zulassungsstellen_count }} Zulassungsstellen</div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</x-layout>
