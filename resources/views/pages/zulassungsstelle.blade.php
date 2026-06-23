<x-layout :title="$title" :description="$description" :canonical="$canonical" :robots="$robots" :schemas="$schemas">
    <nav class="breadcrumb">
        <a href="{{ url('/') }}">Start</a> ›
        <a href="{{ url('/zulassungsstelle') }}">Zulassungsstellen</a> › {{ $stelle->name }}
    </nav>

    <h1>{{ $stelle->name }}</h1>
    @if($stelle->traeger)<p class="muted">{{ $stelle->traeger }}</p>@endif

    @php $v = $ab['cta_text'] ?? 'a'; $ortLabel = $stelle->ort ?: $stelle->name; @endphp
    <p><a class="cta js-reservierung-cta" data-label="zst:{{ $stelle->slug }}" data-variant="{{ $v }}" href="{{ config('portal.reservation_url') }}?utm_source=portal&utm_medium=cta&utm_campaign=zst&zst={{ $stelle->slug }}" rel="nofollow">{{ $v === 'b' ? $ortLabel.': Wunschkennzeichen in 2 Min. sichern →' : 'Wunschkennzeichen für '.$ortLabel.' reservieren →' }}</a></p>

    <h2>Kontakt & Anschrift</h2>
    <table class="info">
        <tr><th>Anschrift</th><td>{{ $stelle->strasse }}@if($stelle->strasse)<br>@endif{{ $stelle->plz }} {{ $stelle->ort }}</td></tr>
        @if($stelle->telefon)<tr><th>Telefon</th><td>{{ $stelle->telefon }}</td></tr>@endif
        @if($stelle->email)<tr><th>E-Mail</th><td>{{ $stelle->email }}</td></tr>@endif
        @if($stelle->website)<tr><th>Website</th><td><a href="{{ $stelle->website }}" rel="nofollow noopener" target="_blank">{{ $stelle->website }}</a></td></tr>@endif
        @if($stelle->termin_url)<tr><th>Terminvergabe</th><td><a class="js-termin" data-label="{{ $stelle->slug }}" href="{{ $stelle->termin_url }}" rel="nofollow noopener" target="_blank">Online-Termin buchen</a></td></tr>@endif
        @if($stelle->bundesland)<tr><th>Bundesland</th><td><a href="{{ url('/bundesland/'.$stelle->bundesland->slug) }}">{{ $stelle->bundesland->name }}</a></td></tr>@endif
    </table>

    @if(is_array($stelle->oeffnungszeiten) && count($stelle->oeffnungszeiten))
        <h2>Öffnungszeiten</h2>
        @if(isset($stelle->oeffnungszeiten['raw']))
            <p>{{ $stelle->oeffnungszeiten['raw'] }}</p>
        @else
            <table class="info">
                @foreach($stelle->oeffnungszeiten as $z)
                    @if(is_array($z))
                        <tr><th>{{ $z['label'] ?? $z['day'] ?? '' }}</th><td>{{ ($z['opens'] ?? '') }}@if(isset($z['closes'])) – {{ $z['closes'] }}@endif</td></tr>
                    @endif
                @endforeach
            </table>
        @endif
    @endif

    @if($stelle->kennzeichenKuerzel->isNotEmpty())
        <h2>Kennzeichen-Kürzel</h2>
        <p>
            @foreach($stelle->kennzeichenKuerzel as $k)
                <a class="badge" href="{{ url('/kennzeichen/'.$k->slug) }}">{{ $k->code }}</a>
            @endforeach
        </p>
    @endif

    @if($stelle->last_imported_at)
        <p class="muted">Datenstand: {{ $stelle->last_imported_at->format('d.m.Y') }}@if($stelle->quelle) · Quelle: {{ $stelle->quelle }}@endif</p>
    @endif
    @if(str_contains((string) $stelle->quelle, 'OpenStreetMap'))
        <p class="muted">Stammdaten © OpenStreetMap-Mitwirkende, <a href="https://opendatacommons.org/licenses/odbl/" rel="nofollow noopener" target="_blank">ODbL</a>. Ohne Gewähr — bitte vor dem Besuch bei der Behörde prüfen.</p>
    @endif

    @if($artikel->isNotEmpty())
        <h2>Passende Ratgeber</h2>
        <ul>
            @foreach($artikel as $a)
                <li><a href="{{ url('/ratgeber/'.$a->slug) }}">{{ $a->titel }}</a></li>
            @endforeach
        </ul>
    @endif

    <x-ad-slot position="zst_unten" />
</x-layout>
