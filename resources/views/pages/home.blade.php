<x-layout :title="$title" :description="$description" :canonical="$canonical" :schemas="$schemas">
    @php $v = $ab['cta_text'] ?? 'a'; @endphp

    <section class="hero reveal in">
        <h1>Wunschkennzeichen reservieren – schnell &amp; amtlich</h1>
        <p class="lead">Verfügbarkeit prüfen, Zulassungsstelle mit Öffnungszeiten &amp; Online-Termin
        finden und dein Wunschkennzeichen in wenigen Minuten sichern.</p>

        <div class="hero-actions">
            <a class="cta js-reservierung-cta" data-label="home" data-variant="{{ $v }}" href="{{ config('portal.reservation_url') }}?utm_source=portal&utm_medium=cta&utm_campaign=home" rel="nofollow">{{ $v === 'b' ? 'Wunschkennzeichen sichern – in 2 Minuten →' : 'Jetzt Wunschkennzeichen prüfen →' }}</a>
            <a class="btn btn-ghost" href="{{ url('/zulassungsstelle') }}">Zulassungsstelle finden</a>
        </div>

        <form class="hero-search" method="get" action="{{ url('/zulassungsstelle') }}" style="margin-top:22px;display:flex;gap:8px;max-width:440px" data-suggest="{{ url('/zulassungsstelle/vorschlaege') }}">
            <input type="search" name="q" placeholder="Stadt oder Zulassungsstelle suchen …" aria-label="Suche"
                   style="flex:1;padding:13px 16px;border:none;border-radius:11px;font-size:1rem;box-shadow:0 8px 24px -10px rgba(0,0,0,.4)">
            <button class="cta" type="submit" style="padding:13px 18px">Suchen</button>
        </form>

        <div class="trust">
            <span>✔︎ Alle deutschen Zulassungsstellen</span>
            <span>🕒 Aktuelle Öffnungszeiten</span>
            <span>📅 Online-Terminlinks</span>
        </div>
    </section>

    @if($kuerzel->isNotEmpty())
        <h2 class="reveal">Beliebte Kennzeichen-Kürzel</h2>
        <p class="reveal reveal-d1">
            @foreach($kuerzel as $k)
                <a class="badge" href="{{ url('/kennzeichen/'.$k->slug) }}">{{ $k->code }}</a>
            @endforeach
        </p>
    @endif

    @if($stellen->isNotEmpty())
        <h2 class="reveal">Zulassungsstellen</h2>
        <div class="grid">
            @foreach($stellen as $s)
                <div class="card reveal {{ 'reveal-d'.($loop->index % 3 + 1) }}">
                    <a href="{{ $s->url() }}">{{ $s->name }}</a>
                    <div class="muted">{{ $s->ort }}@if($s->bundesland) · {{ $s->bundesland->name }}@endif</div>
                </div>
            @endforeach
        </div>
        <p style="margin-top:14px"><a href="{{ url('/zulassungsstelle') }}">Alle Zulassungsstellen nach Bundesland →</a></p>
    @endif

    @if($artikel->isNotEmpty())
        <h2 class="reveal">Ratgeber rund ums Kfz</h2>
        <div class="grid">
            @foreach($artikel as $a)
                <div class="card reveal {{ 'reveal-d'.($loop->index % 3 + 1) }}">
                    <a href="{{ url('/ratgeber/'.$a->slug) }}">{{ $a->titel }}</a>
                </div>
            @endforeach
        </div>
    @endif
</x-layout>
