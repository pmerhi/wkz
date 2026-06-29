<x-layout :title="$title" :description="$description" :canonical="$canonical" :schemas="$schemas">
    <nav class="breadcrumb"><a href="{{ url('/') }}">Start</a> › Formulare</nav>

    <section class="hero hero-sm reveal in">
        <h1>Formulare für die Zulassungsstelle</h1>
        <p class="lead">Kostenlose Muster-Formulare als PDF – Vollmacht, SEPA-Lastschriftmandat, eidesstattliche
        Versicherung &amp; mehr. Herunterladen, ausfüllen, zum Termin mitnehmen.</p>
    </section>

    <div class="box box-info" style="margin:0 0 22px">
        <strong>Für die Online-Zulassung brauchst du keine Formulare.</strong>
        Die Muster helfen nur, wenn du persönlich zur Zulassungsstelle gehst. Viele Vorgänge gehen heute komplett
        digital – siehe <a href="{{ url('/kfz-ratgeber/i-kfz-online-zulassung') }}">i-Kfz – Auto online zulassen</a>.
    </div>

    <div class="grid">
        @foreach($formulare as $slug => $form)
            <div class="card card-dl reveal {{ 'reveal-d'.($loop->index % 3 + 1) }}">
                <strong>{{ $form['titel'] }}</strong>
                <div class="card-desc">{{ $form['beschreibung'] }}</div>
                <a class="btn-dl" href="{{ url('/formulare/'.$slug.'.pdf') }}">⬇ Herunterladen (PDF)</a>
            </div>
        @endforeach
    </div>

    <p class="muted" style="font-size:.85rem;margin-top:22px">
        Alle Formulare sind kostenlose Muster zur Vorbereitung – kein amtliches Dokument, keine Rechtsberatung.
        Einzelne Zulassungsstellen verlangen eigene Vordrucke. Angaben ohne Gewähr.
    </p>
</x-layout>
