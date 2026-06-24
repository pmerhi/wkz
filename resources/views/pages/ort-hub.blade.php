<x-layout :title="$title" :description="$description" :canonical="$canonical" :schemas="$schemas">
    <nav class="breadcrumb">
        <a href="{{ url('/') }}">Start</a> ›
        <a href="{{ url('/kennzeichen') }}">Kennzeichen</a> › Nach Ort
    </nav>

    <section class="hero hero-sm reveal in">
        <h1>Kfz-Kennzeichen nach Ort</h1>
        <p class="lead">Welches Kennzeichen hat dein Ort? Wähle dein Bundesland und finde Stadt oder Gemeinde –
        mit Unterscheidungszeichen, zuständiger Zulassungsstelle und Wunschkennzeichen-Reservierung.</p>
    </section>

    <p class="muted">{{ number_format($gesamt, 0, ',', '.') }} Städte &amp; Gemeinden in {{ $laender->count() }} Bundesländern.</p>

    <div class="grid">
        @foreach($laender as $land)
            <div class="card reveal {{ 'reveal-d'.($loop->index % 3 + 1) }}">
                <a href="{{ url('/kennzeichen/ort/bundesland/'.$land->slug) }}"><strong>{{ $land->name }}</strong></a>
                <div class="muted">{{ number_format($land->ort_count, 0, ',', '.') }} Orte</div>
            </div>
        @endforeach
    </div>
</x-layout>
