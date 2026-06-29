@props(['stelle'])
@php
    $lat = $stelle->lat;
    $lng = $stelle->lng;
@endphp
@if($lat && $lng)
    @once
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <style>
            .standort-karte{height:320px;border-radius:var(--r,14px);overflow:hidden;
                border:1px solid var(--line,#e2e8f0);box-shadow:var(--shadow);margin:.6em 0 .2em;background:var(--soft2,#f8fafc)}
            .standort-karte .leaflet-control-attribution{font-size:.68rem;background:rgba(255,255,255,.85)}
            .sk-pin{width:22px;height:22px;border-radius:50% 50% 50% 0;background:var(--pri,#1d4ed8);
                transform:rotate(-45deg);border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.4)}
            .sk-karte-foot{font-size:.8rem;color:var(--mut,#64748b);margin:.2em 0 0}
            .sk-karte-foot a{color:var(--pri,#1d4ed8)}
        </style>
    @endonce

    <div class="standort-karte"
         data-lat="{{ $lat }}" data-lng="{{ $lng }}"
         data-label="{{ e($stelle->kopf_titel ?: $stelle->name) }}"
         role="img"
         aria-label="Karte: Standort {{ $stelle->name }}, {{ $stelle->strasse }} {{ $stelle->plz }} {{ $stelle->ort }}"></div>
    <p class="sk-karte-foot">
        <a href="https://www.openstreetmap.org/directions?to={{ $lat }}%2C{{ $lng }}" rel="nofollow noopener" target="_blank">Route planen ↗</a>
        · Kartendaten: <a href="https://basemap.de" rel="nofollow noopener" target="_blank">basemap.de</a> / © GeoBasis-DE / BKG
    </p>

    @once
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
        (function () {
            function init() {
                if (!window.L) return;
                document.querySelectorAll('.standort-karte').forEach(function (el) {
                    if (el._skInit) return;
                    el._skInit = true;
                    var lat = parseFloat(el.dataset.lat), lng = parseFloat(el.dataset.lng);
                    if (!lat || !lng) return;

                    var map = L.map(el, { scrollWheelZoom: false, attributionControl: true }).setView([lat, lng], 15);

                    // Offizielle basemap.de-Kacheln (WMTS, Web-Mercator, Farbe).
                    L.tileLayer('https://sgx.geodatenzentrum.de/wmts_basemapde/tile/1.0.0/de_basemapde_web_raster_farbe/default/GLOBAL_WEBMERCATOR/{z}/{y}/{x}.png', {
                        maxZoom: 18,
                        attribution: '&copy; <a href="https://basemap.de" target="_blank" rel="noopener">basemap.de</a> / BKG'
                    }).addTo(map);

                    var icon = L.divIcon({ className: 'sk-pin-wrap', html: '<div class="sk-pin"></div>', iconSize: [22, 22], iconAnchor: [11, 22] });
                    L.marker([lat, lng], { icon: icon, title: el.dataset.label }).addTo(map);
                });
            }
            if (document.readyState !== 'loading') init();
            document.addEventListener('DOMContentLoaded', init);
        })();
        </script>
    @endonce
@endif
