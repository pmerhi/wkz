<x-layout :title="$title" :description="$description" :canonical="$canonical" :schemas="$schemas">
    <nav class="breadcrumb">
        <a href="{{ url('/') }}">Start</a> ›
        <a href="{{ url('/kennzeichen') }}">Kennzeichen</a> › Altkennzeichen
    </nav>

    <section class="hero hero-sm reveal in">
        <h1>Altkennzeichen – wieder eingeführte Kfz-Kennzeichen</h1>
        <p class="lead">
            <strong>Altkennzeichen</strong> sind Unterscheidungszeichen aufgelöster Land- und Stadtkreise,
            die seit der <strong>Kennzeichenliberalisierung</strong> (1. November 2012) wieder ausgegeben
            werden dürfen. Hier findest du alle <strong>{{ $anzahl }}</strong> wieder eingeführten
            Altkennzeichen – mit historischer Bedeutung und heutigem Zulassungsbezirk.
        </p>
        <p style="margin:18px 0 0"><a class="cta js-reservierung-cta" data-label="altkennzeichen:index" href="{{ config('portal.reservation_url') }}?utm_source=portal&utm_medium=cta&utm_campaign=altkennzeichen" rel="nofollow">Wunschkennzeichen mit Altkennzeichen reservieren →</a></p>
    </section>

    <x-altkennzeichen-infografik />

    <p>
        @foreach($gruppen as $name => $g)
            <a class="badge" href="#{{ $g['land']->slug }}">{{ $name }} ({{ count($g['codes']) }})</a>
        @endforeach
    </p>

    @foreach($gruppen as $name => $g)
        <h2 id="{{ $g['land']->slug }}">{{ $name }}</h2>
        <p class="muted">{{ count($g['codes']) }} Altkennzeichen ·
            <a href="{{ url('/zulassungsstelle/'.$g['land']->slug) }}">Zulassungsstellen in {{ $name }} →</a></p>
        <table class="info">
            <thead><tr><th>Kürzel</th><th>Historisch</th><th>Heutiger Zulassungsbezirk</th></tr></thead>
            <tbody>
            @foreach($g['codes'] as $k)
                <tr>
                    <td><a class="badge badge-alt" href="{{ url('/kennzeichen/'.$k->slug) }}">{{ $k->code }}</a></td>
                    <td>{{ $k->historische_stadt ?: '—' }}</td>
                    <td>{{ $k->bedeutung ? \Illuminate\Support\Str::beforeLast($k->bedeutung, ', '.$name) : '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endforeach

    @if(!empty($ohne))
        <h2 id="weitere">Weitere Altkennzeichen</h2>
        <p>
            @foreach($ohne as $k)
                <a class="badge badge-alt" href="{{ url('/kennzeichen/'.$k->slug) }}" title="{{ $k->historische_stadt }}">{{ $k->code }}</a>
            @endforeach
        </p>
    @endif

    <h2>Häufige Fragen zu Altkennzeichen</h2>
    @foreach($faq as $f)
        <h3>{{ $f[0] }}</h3>
        <p>{{ $f[1] }}</p>
    @endforeach

    <p style="margin-top:24px;"><a href="{{ url('/kennzeichen') }}">← Alle Kennzeichen-Kürzel (A–Z)</a></p>

    <x-ad-slot position="altkennzeichen_unten" />
</x-layout>
