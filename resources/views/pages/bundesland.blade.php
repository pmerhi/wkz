<x-layout :title="$title" :description="$description" :canonical="$canonical" :robots="$robots" :schemas="$schemas">
    <nav class="breadcrumb">
        <a href="{{ url('/') }}">Start</a> ›
        <a href="{{ url('/zulassungsstelle') }}">Zulassungsstellen</a> › {{ $land->name }}
    </nav>

    <section class="hero hero-sm reveal in">
        <h1>Zulassungsstellen in {{ $land->name }}</h1>
        @if(! $land->zulassungsstellen->isEmpty())
        <p class="lead">In {{ $land->name }} sind <strong>{{ $land->zulassungsstellen->count() }}</strong>
        Kfz-Zulassungsstellen erfasst – mit Anschrift, Öffnungszeiten, Online-Terminvergabe und den
        Kennzeichen-Kürzeln des Landes.</p>
        @endif
    </section>

    @if($land->zulassungsstellen->isEmpty())
        <p class="muted">Für {{ $land->name }} sind derzeit noch keine Zulassungsstellen erfasst.
        <a href="{{ url('/zulassungsstelle') }}">Zum Verzeichnis →</a></p>
    @else
        @if($kuerzel->isNotEmpty())
        <section class="section reveal">
            <h2>Kennzeichen-Kürzel in {{ $land->name }}</h2>
            <p class="muted">{{ $kuerzel->count() === 1
                ? 'Fahrzeuge aus '.$land->name.' tragen das Unterscheidungszeichen:'
                : 'Fahrzeuge aus '.$land->name.' tragen diese Unterscheidungszeichen:' }}</p>
            <p>
                @foreach($kuerzel as $k)
                    <a class="badge" href="{{ url('/kennzeichen/'.$k->slug) }}">{{ $k->code }}</a>
                @endforeach
            </p>
        </section>
        @endif

        <section class="section reveal">
            <h2>Zulassungsstellen in {{ $land->name }}</h2>
            <p class="muted">{{ $land->zulassungsstellen->count() === 1
                ? 'Zuständige Zulassungsstelle – mit Öffnungszeiten, Online-Termin und Kontakt:'
                : 'Wähle deinen Ort für Öffnungszeiten, Online-Termin und Kontakt:' }}</p>
            <div class="grid">
                @foreach($land->zulassungsstellen as $s)
                    <div class="card">
                        <a href="{{ $s->url() }}">{{ $s->ort ?: $s->name }}</a>
                        @if($s->ort && $s->ort !== $s->name)<div class="muted" style="font-size:.9rem">{{ $s->name }}</div>@endif
                    </div>
                @endforeach
            </div>
        </section>

        @if($artikel->isNotEmpty())
        <section class="section reveal">
            <h2>Ratgeber rund ums Kfz</h2>
            <div class="grid">
                @foreach($artikel as $a)
                    <div class="card"><a href="{{ url('/kfz-ratgeber/'.$a->slug) }}">{{ $a->titel }}</a></div>
                @endforeach
            </div>
        </section>
        @endif
    @endif
</x-layout>
