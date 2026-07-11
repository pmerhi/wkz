<x-layout :title="$title" :description="$description" :canonical="$canonical" :robots="$robots" :schemas="$schemas">
    <nav class="breadcrumb">
        <a href="{{ url('/') }}">Start</a> ›
        <a href="{{ url('/kennzeichen') }}">Kennzeichen</a> › {{ $kuerzel->code }}
    </nav>

    {{-- Interaktiver Wunschkennzeichen-Generator --}}
    <x-kennzeichen-generator :kuerzel="$kuerzel->code" />

    <section class="section reveal">
        <p>
            Das Kfz-Kennzeichen <strong>{{ $kuerzel->code }}</strong> steht für
            <strong>{{ $kuerzel->bedeutung ?: 'einen deutschen Zulassungsbezirk' }}</strong>@if($bundesland)
            im Bundesland <a href="{{ url('/zulassungsstelle/'.$bundesland->slug) }}">{{ $bundesland->name }}</a>@endif.
            Fahrzeuge aus diesem Bezirk tragen das Unterscheidungszeichen „{{ $kuerzel->code }}".
        </p>

        @if($kuerzel->ist_altkennzeichen)
            <p>
                <strong>{{ $kuerzel->code }}</strong> ist ein sogenanntes <strong>Altkennzeichen</strong>: Das
                Unterscheidungszeichen stand ursprünglich für
                <strong>{{ $kuerzel->historische_stadt ?: 'einen früheren Zulassungsbezirk' }}</strong> und wurde
                im Rahmen der <a href="{{ url('/altkennzeichen') }}">Kennzeichenliberalisierung</a> (seit dem
                1. November 2012) wieder zur Zulassung freigegeben. Es kann heute wieder
                {{ $kuerzel->bedeutung ? 'beim '.$kuerzel->bedeutung : 'im zuständigen Zulassungsbezirk' }}
                beantragt werden.
            </p>
        @endif
    </section>

    @if($kuerzel->zulassungsstellen->isNotEmpty())
        <section class="section reveal">
            <h2>Zuständige Zulassungsstelle(n)</h2>
            <div class="grid">
                @foreach($kuerzel->zulassungsstellen as $s)
                    <div class="card">
                        <a href="{{ $s->url() }}">{{ $s->name }}</a>
                        <div class="muted">{{ $s->ort }}@if($s->bundesland) · {{ $s->bundesland->name }}@endif</div>
                    </div>
                @endforeach
            </div>
        </section>
    @else
        <p class="muted">Die zuständige Zulassungsstelle ist hier noch nicht hinterlegt.
        <a href="{{ url('/zulassungsstelle') }}">Zum Zulassungsstellen-Verzeichnis →</a></p>
    @endif

    <section class="hero hero-sm reveal in">
        <h1>Kennzeichen {{ $kuerzel->code }}@if($kuerzel->bedeutung) – {{ $kuerzel->bedeutung }}@endif</h1>
        @if($kuerzel->ist_altkennzeichen)
            <p class="badge badge-alt" style="background:#fff" title="Im Rahmen der Kennzeichenliberalisierung (seit 1. November 2012) wieder eingeführt">↩︎ Altkennzeichen – wieder erhältlich</p>
        @endif
        <p style="margin:16px 0 0"><x-reservierung-cta :label="'kuerzel:'.$kuerzel->code" campaign="kuerzel" /></p>
    </section>

    <x-wusstest-box />

    <x-quiz-teaser :code="$kuerzel->code" />

    <p style="margin-top:24px;"><a href="{{ url('/kennzeichen') }}">← Alle Kennzeichen-Kürzel (A–Z)</a></p>
</x-layout>
