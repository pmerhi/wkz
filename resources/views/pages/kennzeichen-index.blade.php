<x-layout :title="$title" :description="$description" :canonical="$canonical" :schemas="$schemas">
    <nav class="breadcrumb"><a href="{{ url('/') }}">Start</a> › Kennzeichen</nav>

    <h1>Kfz-Kennzeichen Liste — alle Unterscheidungszeichen A–Z</h1>
    <p class="muted">Welche Stadt bzw. welcher Landkreis steckt hinter einem Kennzeichen?
    Hier sind alle deutschen Unterscheidungszeichen — wähle einen Buchstaben.</p>

    <p>
        @foreach($gruppen->keys() as $buchstabe)
            <a class="badge" href="#{{ $buchstabe }}">{{ $buchstabe }}</a>
        @endforeach
    </p>

    @if($altkennzeichen->isNotEmpty())
        <h2 id="altkennzeichen">Altkennzeichen – wieder eingeführte Unterscheidungszeichen</h2>
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
        <h2 id="{{ $buchstabe }}">{{ $buchstabe }}</h2>
        <p>
            @foreach($liste as $k)
                <a class="badge" href="{{ url('/kennzeichen/'.$k->slug) }}" title="{{ $k->bedeutung }}">{{ $k->code }}</a>
            @endforeach
        </p>
    @endforeach
</x-layout>
