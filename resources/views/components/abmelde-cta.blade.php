@props([
    'variant'  => 'block',   // block | hinweis | inline
    'label'    => null,      // Herkunft für Matomo/ab_events, z. B. "zst:wuerzburg"
    'campaign' => 'abmeldung',
    'titel'    => null,
    'text'     => null,
])
@php
    $label = $label ?: request()->path();
    // Serverseitig getrackte Weiterleitung → Conversion ist adblock-fest.
    $href  = url('/go/abmeldung').'?'.http_build_query(['c' => $campaign, 'label' => $label]);
    $bew   = config('abmeldung.bewertung');
    $usps  = config('abmeldung.usps', []);
    $btn   = 'Fahrzeug jetzt online abmelden →';
    // Auf der Landingpage selbst wäre der Link auf die Landingpage sinnlos.
    $zeigeMehr = ! request()->is('kfz-abmeldung');
@endphp

@if($variant === 'inline')
    {{-- class="cta"/"btn" lässt sich von außen mitgeben, z. B. für Hero-Buttons. --}}
    <a {{ $attributes->merge(['class' => 'js-abmelde-cta']) }} data-label="{{ $label }}" href="{{ $href }}" rel="nofollow">{{ $slot->isEmpty() ? 'Kfz online abmelden' : trim($slot) }}</a>

@elseif($variant === 'hinweis')
    <div class="box box-tipp">
        <strong>{{ $titel ?: 'Lieber ohne Amtsbesuch?' }}</strong>
        {{ $text ?: 'Mit unserem Abmeldeservice setzt du dein Fahrzeug komplett online außer Betrieb – kein Behördengang, in 2 Minuten erledigt, mit digitaler Abmeldebestätigung und Geld-zurück-Garantie.' }}
        <a class="js-abmelde-cta" data-label="{{ $label }}" href="{{ $href }}" rel="nofollow">Kfz online abmelden →</a>
    </div>

@else
    <section class="section reveal">
        <div class="abm-cta">
            <span class="abm-tag">Unser Abmeldeservice</span>
            <h2>{{ $titel ?: 'Fahrzeug online abmelden – ohne Gang zur Zulassungsstelle' }}</h2>
            <p>{{ $text ?: 'Außerbetriebsetzung komplett digital: Daten eingeben, absenden, fertig. Kein Termin, keine Warteschlange, kein Papierkram.' }}</p>
            @if($usps)
                <ul class="abm-usps">
                    @foreach($usps as $usp)
                        <li>{{ $usp }}</li>
                    @endforeach
                </ul>
            @endif
            <a class="btn js-abmelde-cta" data-label="{{ $label }}" href="{{ $href }}" rel="nofollow">{{ $btn }}</a>
            @if($zeigeMehr)
                <a class="abm-mehr" href="{{ url('/kfz-abmeldung') }}">So läuft die Abmeldung ab</a>
            @endif
            @if(! empty($bew['wert']))
                <div class="abm-bewertung">★ {{ $bew['wert'] }} von 5 – {{ $bew['anzahl'] }} {{ $bew['quelle'] }}-Rezensionen</div>
            @endif
        </div>
    </section>
@endif
