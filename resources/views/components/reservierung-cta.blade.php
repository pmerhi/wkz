@props(['label' => null, 'campaign' => 'cta', 'experiment' => 'cta_text', 'symbol' => null])
@php
    $variant = $ab[$experiment] ?? 'a';
    $text    = config("experiments.$experiment.cta.$variant")
        ?? config("experiments.$experiment.cta.a", 'Wunschkennzeichen prüfen &amp; reservieren →');
    $label   = $label ?: request()->path();
    // Serverseitig getrackte Weiterleitung → Conversion ist adblock-fest.
    $params = ['c' => $campaign, 'label' => $label, 'v' => $variant];
    // Ortskürzel der Seite mitgeben, damit es im Reservierungsformular vorbelegt ist.
    if ($symbol = trim((string) $symbol)) {
        $params['symbol'] = $symbol;
    }
    $href = url('/go/reservierung').'?'.http_build_query($params);
@endphp
<a class="cta js-reservierung-cta" data-label="{{ $label }}" data-variant="{{ $variant }}" href="{{ $href }}" rel="nofollow">{!! $text !!}</a>
@once
    {{-- Exposure nur, wo der CTA tatsächlich gerendert wird (sinnvoller Nenner für die Conversion-Quote). --}}
    <script>(function(){window._paq=window._paq||[];_paq.push(['trackEvent','Experiment','{{ $experiment }}','{{ $variant }}']);})();</script>
@endonce
