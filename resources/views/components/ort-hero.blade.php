{{--
    Verschmolzener Ort-Hero: Stadtbild als Hintergrund, Titel/Lead in einer Box im
    unteren Bildbereich. Standardmäßig ist das Bild leicht abgedunkelt (Lesbarkeit);
    beim Hover verblasst der Text schnell und das Bild blendet auf volle Sichtbarkeit.
    Die Lizenz-Namensnennung (TASL) bleibt als Overlay sichtbar (Pflicht).

    <x-ort-hero :bild="$heroBild" heading="Kfz-Kennzeichen Bonn">
        Lead-Text mit optionalem <strong>Markup</strong> …
    </x-ort-hero>
--}}
@props([
    'bild',                 // App\Models\Ortbild
    'heading',              // H1-Text
])
@php
    // Namensnennung (TASL) als HTML-String zusammensetzen – robuster als verschachtelte Blade-@ifs.
    $link = fn ($url, $text, $cls = '') => $url
        ? '<a'.($cls ? ' class="'.$cls.'"' : '').' href="'.e($url).'" rel="nofollow noopener" target="_blank">'.$text.'</a>'
        : $text;

    $gemeinfrei = in_array(strtoupper((string) $bild->lizenz), ['CC0', 'CC0 1.0', 'PDM', 'PUBLIC DOMAIN MARK 1.0', 'PUBLIC DOMAIN']);

    $teile = [];
    if ($bild->titel) {
        $teile[] = $link($bild->quelle, '„'.e($bild->titel).'"', 'bc-titel');
    }
    if ($bild->autor) {
        $teile[] = ($bild->titel ? 'von ' : 'Foto: ').$link($bild->autor_url, e($bild->autor));
    }
    $credit = implode(' ', $teile);
    if ($bild->lizenz) {
        $credit .= ($credit ? ($gemeinfrei ? ' · ' : ', lizenziert unter ') : '').$link($bild->lizenz_url, e($bild->lizenz));
    }
    if ($bild->bearbeitet) {
        $credit .= ' · bearbeitet';
    }
@endphp
@once
    <style>
        .ort-hero{position:relative;overflow:hidden;border-radius:20px;margin:0 0 24px;
            box-shadow:var(--shadow-lg);background:#0b1f3a;isolation:isolate}
        .ort-hero__img{display:block;width:100%;height:100%;object-fit:cover;object-position:center top;aspect-ratio:4/3;
            filter:saturate(.9) brightness(.8);transition:filter .6s ease,transform .7s ease}
        @media(min-width:700px){.ort-hero__img{aspect-ratio:16/9}}
        .ort-hero__shade{position:absolute;inset:0;pointer-events:none;z-index:1;transition:opacity .5s ease;
            background:linear-gradient(to top,rgba(4,20,50,.88) 0%,rgba(4,20,50,.5) 34%,rgba(4,20,50,0) 62%)}
        .ort-hero__text{position:absolute;left:0;right:0;bottom:0;z-index:2;
            padding:clamp(20px,4vw,42px);color:#fff;transition:opacity .22s ease,transform .35s ease}
        .ort-hero__text h1{color:#fff;margin:0 0 .35em;max-width:18ch;
            font-size:clamp(1.6rem,3.4vw,2.3rem);text-shadow:0 2px 14px rgba(0,0,0,.55)}
        .ort-hero__text .lead{color:rgba(255,255,255,.94);margin:0;max-width:54ch;
            text-shadow:0 1px 10px rgba(0,0,0,.55)}
        .ort-hero__credit{position:absolute;right:9px;bottom:8px;z-index:3;max-width:min(92%,540px);
            font-size:.72rem;line-height:1.35;color:rgba(255,255,255,.92);
            background:rgba(0,0,0,.42);backdrop-filter:blur(2px);padding:3px 9px;border-radius:8px;
            transition:opacity .3s ease}
        .ort-hero__credit a{color:#fff;text-decoration:underline;text-underline-offset:2px}
        .ort-hero__credit .bc-titel{font-style:italic}
        /* Hover: Schrift verblasst schnell, Bild fadet auf 100 % Sichtbarkeit */
        @media(hover:hover){
            .ort-hero:hover .ort-hero__img{filter:none;transform:scale(1.03)}
            .ort-hero:hover .ort-hero__shade{opacity:0}
            .ort-hero:hover .ort-hero__text{opacity:0;transform:translateY(10px)}
            .ort-hero:hover .ort-hero__credit{opacity:.5}
        }
        /* Gestaffeltes Einblenden beim Laden – 'backwards' lässt den Hover-Effekt intakt */
        @media(prefers-reduced-motion:no-preference){
            .ort-hero__img{animation:ohImg 1s ease backwards}
            .ort-hero__shade{animation:ohFade .9s ease .15s backwards}
            .ort-hero__text{animation:ohText .7s ease .4s backwards}
            .ort-hero__credit{animation:ohFade .6s ease .7s backwards}
        }
        @keyframes ohImg{from{opacity:0;transform:scale(1.06)}to{opacity:1;transform:none}}
        @keyframes ohFade{from{opacity:0}to{opacity:1}}
        @keyframes ohText{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}
    </style>
@endonce
<section {{ $attributes->merge(['class' => 'ort-hero reveal in']) }}>
    <img class="ort-hero__img" src="{{ $bild->bildUrl() }}" alt="{{ $bild->altText() }}"
         @if($bild->width) width="{{ $bild->width }}" @endif
         @if($bild->height) height="{{ $bild->height }}" @endif
         loading="eager" decoding="async" fetchpriority="high">
    <div class="ort-hero__shade"></div>

    <div class="ort-hero__text">
        <h1>{{ $heading }}</h1>
        <p class="lead">{{ $slot }}</p>
    </div>

    @if($credit)
        <figcaption class="ort-hero__credit">{!! $credit !!}</figcaption>
    @endif
</section>
