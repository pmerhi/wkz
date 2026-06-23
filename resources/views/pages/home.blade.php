<x-layout :title="$title" :description="$description" :canonical="$canonical" :schemas="$schemas">
    <h1>Wunschkennzeichen reservieren</h1>
    <p class="muted">Verfügbarkeit prüfen, Zulassungsstelle finden und das Wunschkennzeichen
    online sichern — plus Ratgeber rund um die Kfz-Zulassung.</p>

    @php $v = $ab['cta_text'] ?? 'a'; @endphp
    <p><a class="cta js-reservierung-cta" data-label="home" data-variant="{{ $v }}" href="{{ config('portal.reservation_url') }}?utm_source=portal&utm_medium=cta&utm_campaign=home" rel="nofollow">{{ $v === 'b' ? 'Wunschkennzeichen sichern – in 2 Minuten →' : 'Jetzt Wunschkennzeichen prüfen →' }}</a></p>

    @if($kuerzel->isNotEmpty())
        <h2>Beliebte Kennzeichen-Kürzel</h2>
        <p>
            @foreach($kuerzel as $k)
                <a class="badge" href="{{ url('/kennzeichen/'.$k->slug) }}">{{ $k->code }}</a>
            @endforeach
        </p>
    @endif

    @if($stellen->isNotEmpty())
        <h2>Zulassungsstellen</h2>
        <div class="grid">
            @foreach($stellen as $s)
                <div class="card"><a href="{{ $s->url() }}">{{ $s->name }}</a>
                    <div class="muted">{{ $s->ort }}</div>
                </div>
            @endforeach
        </div>
        <p><a href="{{ url('/zulassungsstelle') }}">Alle Zulassungsstellen →</a></p>
    @endif

    @if($artikel->isNotEmpty())
        <h2>Ratgeber</h2>
        <ul>
            @foreach($artikel as $a)
                <li><a href="{{ url('/ratgeber/'.$a->slug) }}">{{ $a->titel }}</a></li>
            @endforeach
        </ul>
    @endif
</x-layout>
