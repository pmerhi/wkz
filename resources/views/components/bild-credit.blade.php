{{--
    Bild mit rechtssicherer Namensnennung (TASL: Titel, Autor, Source, Lizenz).
    Für Fotos unter Creative-Commons-Lizenz (z. B. von Flickr / Wikimedia Commons).

    Beispiel:
    <x-bild-credit
        src="img/orte/koeln-panorama.jpg"
        alt="Blick über Köln mit Dom und Rheinufer"
        titel="Köln Panorama"
        autor="Max Mustermann"
        autor-url="https://www.flickr.com/photos/maxmustermann"
        quelle="https://www.flickr.com/photos/maxmustermann/123456789"
        lizenz="CC BY 4.0"
        :bearbeitet="true"
        width="1200" height="675" />

    Pflichtangaben je nach Lizenz:
    - CC BY / BY-SA / BY-ND ...  → Autor + Quelle + Lizenz(-Link) zwingend, Titel empfohlen.
    - CC0 / Public Domain        → keine Pflicht, Nennung dennoch als Nachweis empfohlen.
    Bei Bearbeitung (Zuschnitt, Farbe, Montage) :bearbeitet="true" setzen.
--}}
@props([
    'src',                    // Pfad relativ zu /public (asset()) ODER absolute URL
    'alt' => '',              // Alt-Text (Barrierefreiheit/SEO) – nicht der Credit!
    'titel' => null,          // Titel des Werks (ab CC 4.0 optional, empfohlen)
    'autor' => null,          // Name/Nutzername des Urhebers
    'autorUrl' => null,       // Link auf das Urheber-Profil (optional)
    'quelle' => null,         // Link zur Original-Fotoseite (Flickr etc.)
    'lizenz' => null,         // Lizenzkürzel, z. B. "CC BY 4.0", "CC0", "PDM"
    'lizenzUrl' => null,      // Lizenz-Link (wird sonst automatisch abgeleitet)
    'bearbeitet' => false,    // true = Hinweis "Bild bearbeitet" ausgeben
    'width' => null,
    'height' => null,
    'loading' => 'lazy',      // 'eager' für Bilder above the fold
    'variant' => 'hero',      // 'hero' = volle Breite, 'footer' = kleiner/zentriert
])
@php
    // Absolute URL (http/https/protokoll-relativ) direkt, sonst über asset() aus /public.
    $imgSrc = \Illuminate\Support\Str::startsWith($src, ['http://', 'https://', '//'])
        ? $src : asset($src);

    // Lizenz-Link automatisch aus dem Kürzel ableiten, wenn nicht gesetzt.
    $lizenzMap = [
        'CC0'         => 'https://creativecommons.org/publicdomain/zero/1.0/deed.de',
        'CC0 1.0'     => 'https://creativecommons.org/publicdomain/zero/1.0/deed.de',
        'PDM'         => 'https://creativecommons.org/publicdomain/mark/1.0/deed.de',
        'CC BY 4.0'   => 'https://creativecommons.org/licenses/by/4.0/deed.de',
        'CC BY-SA 4.0'=> 'https://creativecommons.org/licenses/by-sa/4.0/deed.de',
        'CC BY-ND 4.0'=> 'https://creativecommons.org/licenses/by-nd/4.0/deed.de',
        'CC BY 3.0'   => 'https://creativecommons.org/licenses/by/3.0/deed.de',
        'CC BY-SA 3.0'=> 'https://creativecommons.org/licenses/by-sa/3.0/deed.de',
        'CC BY 2.0'   => 'https://creativecommons.org/licenses/by/2.0/deed.de',
        'CC BY-SA 2.0'=> 'https://creativecommons.org/licenses/by-sa/2.0/deed.de',
    ];
    $lizUrl = $lizenzUrl ?: ($lizenz ? ($lizenzMap[trim($lizenz)] ?? null) : null);

    // Bei CC0/Public Domain ist keine Namensnennung Pflicht.
    $gemeinfrei = in_array(strtoupper(trim((string) $lizenz)), ['CC0', 'CC0 1.0', 'PDM', 'PUBLIC DOMAIN']);
    $hatCredit = $titel || $autor || $quelle || $lizenz;
@endphp
@once
    <style>
        .bild-credit{margin:1.2em 0}
        .bild-credit.is-footer{max-width:560px;margin-left:auto;margin-right:auto}
        /* Zwei Footer-Bilder nebeneinander (ab 640px), einheitliches 3:2-Format */
        .bild-credit-grid{display:grid;gap:16px;grid-template-columns:1fr;margin:1.2em 0}
        .bild-credit-grid .bild-credit{margin:0}
        .bild-credit-grid .bild-credit img{aspect-ratio:3/2;object-fit:cover}
        @media(min-width:640px){.bild-credit-grid{grid-template-columns:1fr 1fr}}
        /* Zeitversetztes Einblenden der beiden Footer-Bilder (nutzt die .reveal-Transition) */
        .bild-credit-grid .bild-credit:nth-child(2){transition-delay:.2s}
        .bild-credit-grid .bild-credit:nth-child(3){transition-delay:.4s}
        .bild-credit img{display:block;width:100%;height:auto;border-radius:var(--r,14px);
            border:1px solid var(--line,#e2e8f0);box-shadow:var(--shadow)}
        .bild-credit figcaption{font-size:.78rem;line-height:1.45;color:var(--mut,#64748b);
            margin:.5em 0 0;padding:0 .2em}
        .bild-credit figcaption a{color:var(--mut,#64748b);text-decoration:underline;text-underline-offset:2px}
        .bild-credit figcaption a:hover{color:var(--pri,#1d4ed8)}
        .bild-credit .bc-titel{font-style:italic}
    </style>
@endonce
<figure class="bild-credit reveal {{ $variant === 'footer' ? 'is-footer' : '' }}">
    <img src="{{ $imgSrc }}" alt="{{ $alt }}"
         @if($width) width="{{ $width }}" @endif
         @if($height) height="{{ $height }}" @endif
         loading="{{ $loading }}" decoding="async">
    @if($hatCredit)
        <figcaption>
            @if($gemeinfrei)
                @if($autor)Foto: {{ $autor }} ·@else Foto:@endif
                @if($lizUrl)<a href="{{ $lizUrl }}" rel="nofollow noopener" target="_blank">{{ $lizenz }}</a>@else{{ $lizenz }}@endif
                @if($quelle)(<a href="{{ $quelle }}" rel="nofollow noopener" target="_blank">Quelle</a>)@endif
            @else
                @if($titel)
                    @if($quelle)<a class="bc-titel" href="{{ $quelle }}" rel="nofollow noopener" target="_blank">„{{ $titel }}"</a>@else<span class="bc-titel">„{{ $titel }}"</span>@endif
                @endif
                @if($autor)
                    von @if($autorUrl)<a href="{{ $autorUrl }}" rel="nofollow noopener" target="_blank">{{ $autor }}</a>@else{{ $autor }}@endif
                @endif
                @if($quelle && !$titel), <a href="{{ $quelle }}" rel="nofollow noopener" target="_blank">Quelle</a>@endif
                @if($lizenz),
                    lizenziert unter @if($lizUrl)<a href="{{ $lizUrl }}" rel="nofollow noopener" target="_blank">{{ $lizenz }}</a>@else{{ $lizenz }}@endif
                @endif
            @endif
            @if($bearbeitet) · Bild bearbeitet (zugeschnitten/angepasst)@endif
        </figcaption>
    @endif
</figure>
