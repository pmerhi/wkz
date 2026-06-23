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
            <p>
                @foreach($kuerzel as $k)
                    <a class="badge" href="{{ url('/kennzeichen/'.$k->slug) }}">{{ $k->code }}</a>
                @endforeach
            </p>
        </section>
        @endif

        <section class="section reveal">
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
        </section>

        @if($artikel->isNotEmpty())
        <section class="section reveal">
            <h2>Ratgeber rund ums Kfz</h2>
            <div class="grid">
                @foreach($artikel as $a)
                    <div class="card"><a href="{{ url('/ratgeber/'.$a->slug) }}">{{ $a->titel }}</a></div>
                @endforeach
            </div>
        </section>
        @endif
    @endif
</x-layout>
