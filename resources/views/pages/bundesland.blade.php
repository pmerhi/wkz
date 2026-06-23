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

        <h2>Zulassungsstellen in {{ $land->name }}</h2>
        @php $buchstaben = $gruppen->keys(); @endphp
        <p>
            @foreach($buchstaben as $b)
                <a class="badge" href="#buchstabe-{{ $b }}">{{ $b }}</a>
            @endforeach
        </p>
        @foreach($gruppen as $b => $liste)
            <h3 id="buchstabe-{{ $b }}">{{ $b }}</h3>
            <ul class="stellen-liste">
                @foreach($liste as $s)
                    <li><a href="{{ $s->url() }}">{{ $s->ort ?: $s->name }}</a>@if($s->ort && $s->ort !== $s->name)<span class="muted"> — {{ $s->name }}</span>@endif</li>
                @endforeach
            </ul>
        @endforeach

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
