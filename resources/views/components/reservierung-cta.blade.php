@props(['label' => null, 'campaign' => 'cta', 'experiment' => 'cta_text'])
@php
    $variant = $ab[$experiment] ?? 'a';
    $text    = config("experiments.$experiment.cta.$variant")
        ?? config("experiments.$experiment.cta.a", 'Wunschkennzeichen prüfen &amp; reservieren →');
    $label   = $label ?: request()->path();
    // Serverseitig getrackte Weiterleitung → Conversion ist adblock-fest.
    $href = url('/go/reservierung').'?'.http_build_query(['c' => $campaign, 'label' => $label, 'v' => $variant]);
@endphp
<a class="cta js-reservierung-cta" data-label="{{ $label }}" data-variant="{{ $variant }}" href="{{ $href }}" rel="nofollow">{!! $text !!}</a>
@once
    {{-- Exposure nur, wo der CTA tatsächlich gerendert wird (sinnvoller Nenner für die Conversion-Quote). --}}
    <script>(function(){window._paq=window._paq||[];_paq.push(['trackEvent','Experiment','{{ $experiment }}','{{ $variant }}']);})();</script>
@endonce
