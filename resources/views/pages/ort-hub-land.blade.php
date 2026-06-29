<x-layout :title="$title" :description="$description" :canonical="$canonical" :robots="$robots ?? 'index,follow'" :schemas="$schemas">
    <nav class="breadcrumb">
        <a href="{{ url('/') }}">Start</a> ›
        <a href="{{ url('/kennzeichen') }}">Kennzeichen</a> ›
        <a href="{{ url('/kennzeichen/ort') }}">Nach Ort</a> › {{ $land->name }}
    </nav>

    <section class="hero hero-sm reveal in">
        <h1>Kfz-Kennzeichen in {{ $land->name }}</h1>
        <p class="lead">Alle Städte und Gemeinden in {{ $land->name }} mit ihrem Unterscheidungszeichen –
        nach Landkreis geordnet. Klicke auf einen Ort für Kennzeichen, Zulassungsstelle und Wunschkennzeichen.</p>
    </section>

    @if($gruppen->isEmpty())
        <p class="muted">Für {{ $land->name }} sind noch keine Ort-Seiten verfügbar.
            <a href="{{ url('/kennzeichen/ort') }}">Zurück zur Übersicht →</a></p>
    @else
        <p class="muted">{{ number_format($anzahl, 0, ',', '.') }} Orte in {{ $gruppen->count() }} Zulassungsbezirken.</p>
        @foreach($gruppen as $grp)
            <section class="section reveal">
                <h2>Zulassungsbezirk {{ $grp['label'] }}
                    @if($grp['kuerzel']->isNotEmpty())<span class="muted" style="font-weight:400">·
                        @foreach($grp['kuerzel'] as $k)<a class="badge" href="{{ url('/kennzeichen/'.$k->slug) }}">{{ $k->code }}</a>@endforeach
                    </span>@endif
                </h2>
                <div class="grid">
                    @foreach($grp['orte'] as $g)
                        <div class="card"><a href="{{ url('/wunschkennzeichen/'.$g->slug) }}">{{ $g->name }}</a></div>
                    @endforeach
                </div>
            </section>
        @endforeach
    @endif
</x-layout>
