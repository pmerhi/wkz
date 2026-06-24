@php
    $matomoUrl = config('portal.matomo_url');
    $siteId    = config('portal.matomo_site_id');
@endphp
@if($matomoUrl && $siteId)
@php
    $base    = rtrim($matomoUrl, '/').'/';
    $resHost = parse_url((string) config('portal.reservation_url'), PHP_URL_HOST);
@endphp
<script>
    var _paq = window._paq = window._paq || [];
    _paq.push(['disableCookies']);          // cookieless: consent-frei (Variante A)
    _paq.push(['enableLinkTracking']);
    @if($resHost)
    _paq.push(['setDomains', ['*.{{ request()->getHost() }}', '*.{{ $resHost }}']]);
    _paq.push(['enableCrossDomainLinking']); // Funnel Portal -> Reservierungs-Domain
    @endif
    (function () {
        var u = "{{ $base }}";
        _paq.push(['setTrackerUrl', u + 'matomo.php']);
        _paq.push(['setSiteId', '{{ $siteId }}']);
        var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
        g.async = true; g.src = u + 'matomo.js'; s.parentNode.insertBefore(g, s);
    })();

    // A/B-Exposure wird dort emittiert, wo der CTA tatsächlich gerendert wird
    // (x-reservierung-cta) – so hat die Conversion-Quote je Variante einen sinnvollen Nenner.

    // Event-Tracking für Schlüssel-Interaktionen
    document.addEventListener('click', function (e) {
        var a = e.target.closest('a');
        if (!a) return;
        if (a.classList.contains('js-reservierung-cta')) {
            var v = a.getAttribute('data-variant');
            var label = (a.getAttribute('data-label') || location.pathname) + (v ? '|v=' + v : '');
            _paq.push(['trackEvent', 'Reservierung', 'CTA-Klick', label]);
        } else if (a.classList.contains('js-affiliate')) {
            _paq.push(['trackEvent', 'Affiliate', 'Klick', a.getAttribute('data-label') || a.getAttribute('href')]);
        } else if (a.classList.contains('js-termin')) {
            _paq.push(['trackEvent', 'Zulassungsstelle', 'Termin-Link', a.getAttribute('data-label') || '']);
        }
    });
</script>
<noscript><img src="{{ $base }}matomo.php?idsite={{ $siteId }}&rec=1" style="border:0" alt=""></noscript>
@endif
