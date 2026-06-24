<x-layout :title="$title" :description="$description" :canonical="$canonical" :schemas="$schemas">
    <nav class="breadcrumb"><a href="{{ url('/') }}">Start</a> › Kennzeichen</nav>

    <section class="hero hero-sm reveal in">
        <h1>Kfz-Kennzeichen Liste – alle Unterscheidungszeichen A–Z</h1>
        <p class="lead">Welche Stadt bzw. welcher Landkreis steckt hinter einem Kennzeichen?
        Hier sind alle deutschen Unterscheidungszeichen – wähle einen Buchstaben.</p>
        <p style="margin:14px 0 0">
            @foreach($gruppen->keys() as $buchstabe)
                <a class="badge" href="#{{ $buchstabe }}" style="background:rgba(255,255,255,.18);border-color:rgba(255,255,255,.35);color:#fff">{{ $buchstabe }}</a>
            @endforeach
        </p>
        <p style="margin:16px 0 0">
            <a class="btn" href="{{ url('/kennzeichen/ort') }}">🔎 Kennzeichen nach Ort suchen →</a>
            <a class="btn" href="{{ url('/kennzeichen-quiz') }}" style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.35)">🎮 Kennzeichen-Quiz spielen</a>
        </p>
    </section>

    @if($altkennzeichen->isNotEmpty())
        <h2 id="altkennzeichen" class="reveal">Altkennzeichen – wieder eingeführte Unterscheidungszeichen</h2>
        <p class="muted">Diese {{ $altkennzeichen->count() }} Kennzeichen waren ausgelaufen und sind seit der
        Kennzeichenliberalisierung (1. November 2012) wieder erhältlich – ideal als Wunschkennzeichen mit
        regionalem Bezug. <a href="{{ url('/altkennzeichen') }}">Zur Altkennzeichen-Liste nach Bundesland →</a></p>
        <p>
            @foreach($altkennzeichen as $k)
                <a class="badge badge-alt" href="{{ url('/kennzeichen/'.$k->slug) }}" title="{{ $k->historische_stadt }}@if($k->bedeutung) – heute {{ $k->bedeutung }}@endif">{{ $k->code }}</a>
            @endforeach
        </p>
    @endif

    @foreach($gruppen as $buchstabe => $liste)
        <h2 id="{{ $buchstabe }}" class="reveal">{{ $buchstabe }}</h2>
        <p class="reveal reveal-d1">
            @foreach($liste as $k)
                <a class="badge" href="{{ url('/kennzeichen/'.$k->slug) }}" title="{{ $k->bedeutung }}">{{ $k->code }}</a>
            @endforeach
        </p>
    @endforeach
</x-layout>
