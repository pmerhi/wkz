<x-layout :title="$title" :description="$description" :canonical="$canonical" :robots="$robots" :schemas="$schemas">
    <nav class="breadcrumb">
        <a href="{{ url('/') }}">Start</a> ›
        <a href="{{ url('/zulassungsstelle') }}">Zulassungsstellen</a> › {{ $land->name }}
    </nav>

    <h1>Zulassungsstellen in {{ $land->name }}</h1>

    @if($land->zulassungsstellen->isEmpty())
        <p class="muted">Für {{ $land->name }} sind derzeit noch keine Zulassungsstellen erfasst.
        <a href="{{ url('/zulassungsstelle') }}">Zum Verzeichnis →</a></p>
    @else
        <p>In {{ $land->name }} sind <strong>{{ $land->zulassungsstellen->count() }}</strong>
        Kfz-Zulassungsstellen erfasst. Hier findest du Anschrift, Öffnungszeiten und – wo
        vorhanden – die Online-Terminvergabe sowie die Kennzeichen-Kürzel des Landes.</p>

        @if($kuerzel->isNotEmpty())
            <h2>Kennzeichen-Kürzel in {{ $land->name }}</h2>
            <p>
                @foreach($kuerzel as $k)
                    <a class="badge" href="{{ url('/kennzeichen/'.$k->slug) }}">{{ $k->code }}</a>
                @endforeach
            </p>
        @endif

        <h2>Zulassungsstellen</h2>
        <div class="grid">
            @foreach($land->zulassungsstellen as $s)
                <div class="card">
                    <a href="{{ url('/zulassungsstelle/'.$s->slug) }}">{{ $s->name }}</a>
                    <div class="muted">{{ $s->ort }}</div>
                </div>
            @endforeach
        </div>

        @if($artikel->isNotEmpty())
            <h2>Ratgeber</h2>
            <ul>
                @foreach($artikel as $a)
                    <li><a href="{{ url('/ratgeber/'.$a->slug) }}">{{ $a->titel }}</a></li>
                @endforeach
            </ul>
        @endif
    @endif
</x-layout>
