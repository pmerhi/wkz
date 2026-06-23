<x-layout :title="$title" :description="$description" :canonical="$canonical" :robots="$robots ?? 'index,follow'" :schemas="$schemas">
    <nav class="breadcrumb"><a href="{{ url('/') }}">Start</a> › Zulassungsstellen</nav>

    {{-- Suchergebnis-Modus --}}
    @if($q !== '')
        <h1>Zulassungsstelle suchen: „{{ $q }}"</h1>
        <form method="get" action="{{ url('/zulassungsstelle') }}" style="margin:12px 0;">
            <input type="search" name="q" value="{{ $q }}" placeholder="Stadt oder Behörde …" style="padding:8px;min-width:240px;">
            <button class="cta" style="padding:8px 16px;">Suchen</button>
        </form>
        @if($treffer->isEmpty())
            <p class="muted">Keine Treffer. <a href="{{ url('/zulassungsstelle') }}">Alle Zulassungsstellen nach Bundesland →</a></p>
        @else
            <div class="grid">
                @foreach($treffer as $s)
                    <div class="card">
                        <a href="{{ url('/zulassungsstelle/'.$s->slug) }}">{{ $s->name }}</a>
                        <div class="muted">{{ $s->ort }}@if($s->bundesland) · {{ $s->bundesland->name }}@endif</div>
                    </div>
                @endforeach
            </div>
        @endif
    @else
        {{-- Hub-Modus: nach Bundesland gebündelt --}}
        <h1>Zulassungsstellen in Deutschland</h1>
        <p class="muted">Adressen, Öffnungszeiten und Online-Terminvergabe — gegliedert nach Bundesland.</p>

        <form method="get" action="{{ url('/zulassungsstelle') }}" style="margin:12px 0;">
            <input type="search" name="q" placeholder="Stadt oder Behörde suchen …" style="padding:8px;min-width:240px;">
            <button class="cta" style="padding:8px 16px;">Suchen</button>
        </form>

        @if($laender->isEmpty())
            <p class="muted">Noch keine Zulassungsstellen erfasst.</p>
        @else
            <p>
                @foreach($laender as $land)
                    <a class="badge" href="#{{ $land->slug }}">{{ $land->name }} ({{ $land->zulassungsstellen->count() }})</a>
                @endforeach
            </p>

            @foreach($laender as $land)
                <h2 id="{{ $land->slug }}">{{ $land->name }}</h2>
                <p><a href="{{ url('/bundesland/'.$land->slug) }}">Alle Stellen in {{ $land->name }} →</a></p>
                <div class="grid">
                    @foreach($land->zulassungsstellen as $s)
                        <div class="card">
                            <a href="{{ url('/zulassungsstelle/'.$s->slug) }}">{{ $s->name }}</a>
                            <div class="muted">{{ $s->ort }}</div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        @endif
    @endif
</x-layout>
