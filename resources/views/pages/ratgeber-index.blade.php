<x-layout :title="$title" :description="$description" :canonical="$canonical" :schemas="$schemas">
    <nav class="breadcrumb"><a href="{{ url('/') }}">Start</a> › Ratgeber</nav>

    <section class="hero hero-sm reveal in">
        <h1>Ratgeber rund um die Kfz-Zulassung</h1>
        <p class="lead">Anmelden, abmelden, ummelden, Wunschkennzeichen &amp; Co. – verständlich erklärt,
        Schritt für Schritt.</p>
    </section>

    @if($artikel->isEmpty())
        <p class="muted">Noch keine Artikel veröffentlicht.</p>
    @else
        <div class="grid">
            @foreach($artikel as $a)
                <div class="card reveal {{ 'reveal-d'.($loop->index % 3 + 1) }}">
                    <a href="{{ url('/ratgeber/'.$a->slug) }}">{{ $a->titel }}</a>
                    @if($a->kategorie)<div class="muted" style="font-size:.82rem">{{ $a->kategorie->name }}</div>@endif
                    @if($a->intro)<div class="muted">{{ \Illuminate\Support\Str::limit($a->intro, 120) }}</div>@endif
                </div>
            @endforeach
        </div>
    @endif
</x-layout>
