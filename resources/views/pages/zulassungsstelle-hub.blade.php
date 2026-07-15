@php
    $kopfNav = [
        ['href' => '#standorte',   'label' => 'Standorte'],
        ['href' => '#reservieren', 'label' => 'Wunschkennzeichen'],
        ['href' => '#online',      'label' => 'Online-Zulassung'],
    ];
    $anzahl = $standorte->count();
@endphp
<x-layout :title="$title" :description="$description" :canonical="$canonical" :robots="$robots"
          :schemas="$schemas" :brand="'Zulassungsstelle '.$ortLabel" :nav-links="$kopfNav">

    <style>
        .hub-intro{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:0 0 6px}
        .hub-count{background:var(--pri);color:#fff;font-weight:700;font-size:.8rem;padding:3px 10px;border-radius:999px}
        .hub-standort{border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow);
            padding:18px 20px;margin:0 0 18px;background:#fff}
        .hub-standort > h3{margin:0 0 4px;font-size:1.2rem}
        .hub-standort .adr{color:var(--mut);margin:0 0 10px}
        .hub-standort .kontaktzeile{display:flex;flex-wrap:wrap;gap:8px 18px;font-size:.9rem;margin:0 0 12px}
        .hub-standort .kontaktzeile a{color:var(--pri)}
        [data-theme="dark"] .hub-standort{background:var(--soft2)}
    </style>

    {{-- Intro (H1 steht bereits im Header-Brand, um Doppel-H1 zu vermeiden) --}}
    <div class="hub-intro">
        <span class="hub-count">{{ $anzahl }} {{ $anzahl === 1 ? 'Standort' : 'Standorte' }} in {{ $ortLabel }}</span>
    </div>
    <p class="lead">Alle Kfz-Zulassungsstellen in {{ $ortLabel }} auf einen Blick – mit Adresse, heutigen
        Öffnungszeiten und Online-Termin. Dein Wunschkennzeichen kannst du gleich hier prüfen und reservieren.</p>

    {{-- Reservierungsmaske (einmal, oben) --}}
    <span id="reservieren"></span>
    <x-kennzeichen-generator :kuerzel="$kuerzel?->code" />

    {{-- Standorte --}}
    <section class="section reveal" id="standorte">
        <h2>Standorte in {{ $ortLabel }}</h2>
        @foreach($standorte as $loc)
            @php
                $hatHours = is_array($loc->oeffnungszeiten) && count($loc->oeffnungszeiten) && ! isset($loc->oeffnungszeiten['raw']);
            @endphp
            <article class="hub-standort reveal">
                <h3>{{ $loc->anzeigeName() }}@if(is_null($loc->parent_id)) <span class="hub-count">Hauptamt</span>@endif</h3>
                <p class="adr">{{ $loc->strasse }}@if($loc->strasse)<br>@endif{{ $loc->plz }} {{ $loc->ort }}</p>
                <div class="kontaktzeile">
                    @if($loc->telefon)<span>📞 {{ $loc->telefon }}</span>@endif
                    @if($loc->email)<span>✉️ <a href="mailto:{{ $loc->email }}">{{ $loc->email }}</a></span>@endif
                    @if($loc->website)<span>🌐 <a href="{{ $loc->website }}" rel="nofollow noopener" target="_blank">Website</a></span>@endif
                    @if($loc->termin_url)<span>📅 <a class="js-termin" data-label="{{ $loc->slug }}" href="{{ $loc->termin_url }}" rel="nofollow noopener" target="_blank">Online-Termin →</a></span>@endif
                </div>
                @if($hatHours)
                    <x-oeffnungszeiten :data="$loc->oeffnungszeiten" />
                @endif
                <x-standort-karte :stelle="$loc" />
            </article>
        @endforeach
    </section>

    {{-- Online-Zulassung (i-Kfz) --}}
    <section class="section reveal" id="online">
        <div class="feature">
            <span class="tag-new">Neu · i-Kfz Stufe 4</span>
            <h2>Auto online zulassen – ganz ohne Amtsbesuch</h2>
            <p class="lead-intro">Viele Vorgänge in {{ $ortLabel }} gehen komplett digital: An-, Ab- und Ummeldung
                rund um die Uhr über das <a href="{{ url('/kfz-ratgeber/i-kfz-online-zulassung') }}">i-Kfz-Portal</a>.</p>
            <p style="margin:14px 0 0"><a class="btn" href="{{ url('/kfz-ratgeber/i-kfz-online-zulassung') }}">So funktioniert i-Kfz →</a></p>
        </div>
    </section>

    <p class="muted" style="font-size:.82rem">Angaben ohne Gewähr – bitte vor dem Besuch prüfen.
        <br><em>Prototyp-Ansicht (noindex). Live-Einzelseiten bleiben vorerst unter /zulassungsstelle/{slug}.</em></p>
</x-layout>
