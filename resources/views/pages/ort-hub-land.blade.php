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
                <h2>Zulassungsbezirk {{ $grp['label'] }}</h2>
                @if($grp['kuerzel']->isNotEmpty())
                <div class="kzs-liste kzs-liste--kompakt" style="margin:-4px 0 14px">
                    @foreach($grp['kuerzel'] as $k)
                        <x-kennzeichen-schild :code="$k->code" :href="url('/kennzeichen/'.$k->slug)" />
                    @endforeach
                </div>
                @endif
                <div class="grid">
                    @foreach($grp['orte'] as $g)
                        <div class="card"><a href="{{ url('/wunschkennzeichen/'.$g->slug) }}">{{ $g->name }}</a></div>
                    @endforeach
                </div>
            </section>
        @endforeach
    @endif
</x-layout>
