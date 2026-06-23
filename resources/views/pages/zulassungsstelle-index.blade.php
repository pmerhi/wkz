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
                        <a href="{{ $s->url() }}">{{ $s->name }}</a>
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
            <p class="muted">Wähle dein Bundesland, um die Zulassungsstellen mit Anschrift,
            Öffnungszeiten und Online-Terminvergabe zu sehen.</p>
            <div class="grid">
                @foreach($laender as $land)
                    <div class="card">
                        <a href="{{ url('/zulassungsstelle/'.$land->slug) }}"><strong>{{ $land->name }}</strong></a>
                        <div class="muted">{{ $land->zulassungsstellen_count }} Zulassungsstellen</div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</x-layout>
