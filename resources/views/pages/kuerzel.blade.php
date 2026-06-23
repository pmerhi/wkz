<x-layout :title="$title" :description="$description" :canonical="$canonical" :robots="$robots" :schemas="$schemas">
    <nav class="breadcrumb">
        <a href="{{ url('/') }}">Start</a> ›
        <a href="{{ url('/kennzeichen') }}">Kennzeichen</a> › {{ $kuerzel->code }}
    </nav>

    <h1>Kennzeichen {{ $kuerzel->code }}@if($kuerzel->bedeutung) — {{ $kuerzel->bedeutung }}@endif</h1>

    @if($kuerzel->ist_altkennzeichen)
        <p class="badge badge-alt" title="Im Rahmen der Kennzeichenliberalisierung (seit 1. November 2012) wieder eingeführt">↩︎ Altkennzeichen – wieder erhältlich</p>
    @endif

    <p>
        Das Kfz-Kennzeichen <strong>{{ $kuerzel->code }}</strong> steht für
        <strong>{{ $kuerzel->bedeutung ?: 'einen deutschen Zulassungsbezirk' }}</strong>@if($bundesland)
        im Bundesland <a href="{{ url('/bundesland/'.$bundesland->slug) }}">{{ $bundesland->name }}</a>@endif.
        Fahrzeuge aus diesem Bezirk tragen das Unterscheidungszeichen „{{ $kuerzel->code }}".
    </p>

    @if($kuerzel->ist_altkennzeichen)
        <p>
            <strong>{{ $kuerzel->code }}</strong> ist ein sogenanntes <strong>Altkennzeichen</strong>: Das
            Unterscheidungszeichen stand ursprünglich für
            <strong>{{ $kuerzel->historische_stadt ?: 'einen früheren Zulassungsbezirk' }}</strong> und wurde
            im Rahmen der <a href="{{ url('/kennzeichen') }}">Kennzeichenliberalisierung</a> (seit dem
            1. November 2012) wieder zur Zulassung freigegeben. Es kann heute wieder
            {{ $kuerzel->bedeutung ? 'beim '.$kuerzel->bedeutung : 'im zuständigen Zulassungsbezirk' }}
            beantragt werden.
        </p>
    @endif

    <p><a class="cta js-reservierung-cta" data-label="kuerzel:{{ $kuerzel->code }}" data-variant="{{ $ab['cta_text'] ?? 'a' }}" href="{{ config('portal.reservation_url') }}?utm_source=portal&utm_medium=cta&utm_campaign=kuerzel&code={{ $kuerzel->code }}" rel="nofollow">{{ ($ab['cta_text'] ?? 'a') === 'b' ? $kuerzel->code.': jetzt in 2 Minuten sichern →' : 'Wunschkennzeichen '.$kuerzel->code.' reservieren →' }}</a></p>

    @if($kuerzel->zulassungsstellen->isNotEmpty())
        <h2>Zuständige Zulassungsstelle(n)</h2>
        <div class="grid">
            @foreach($kuerzel->zulassungsstellen as $s)
                <div class="card">
                    <a href="{{ url('/zulassungsstelle/'.$s->slug) }}">{{ $s->name }}</a>
                    <div class="muted">{{ $s->ort }}@if($s->bundesland) · {{ $s->bundesland->name }}@endif</div>
                </div>
            @endforeach
        </div>
    @else
        <p class="muted">Die zuständige Zulassungsstelle ist hier noch nicht hinterlegt.
        <a href="{{ url('/zulassungsstelle') }}">Zum Zulassungsstellen-Verzeichnis →</a></p>
    @endif

    <p style="margin-top:24px;"><a href="{{ url('/kennzeichen') }}">← Alle Kennzeichen-Kürzel (A–Z)</a></p>
</x-layout>
