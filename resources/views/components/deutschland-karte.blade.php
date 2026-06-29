@props([
    'daten' => [],           // [ 'Bayern' => ['slug'=>..., 'count'=>3, 'codes'=>[['code'=>'AIB','slug'=>'aib','stadt'=>'Bad Aibling'], ...]], ... ]
    'titel' => 'Altkennzeichen nach Bundesland',
    'intro' => 'Klicke auf ein Bundesland, um die dort wieder eingeführten Altkennzeichen zu sehen.',
])
@php
    // Choropleth-Stufen anhand der Anzahl je Bundesland bestimmen.
    $counts = array_map(fn ($d) => (int) ($d['count'] ?? 0), $daten);
    $max = $counts ? max($counts) : 0;
    $karteJson = [];
    foreach ($daten as $name => $d) {
        $karteJson[$name] = [
            'slug'  => $d['slug'] ?? null,
            'count' => (int) ($d['count'] ?? 0),
            'codes' => array_values($d['codes'] ?? []),
        ];
    }
@endphp

<div class="de-karte-wrap reveal" data-de-karte data-max="{{ $max }}">
    <h2 class="de-karte-titel">{{ $titel }}</h2>
    <p class="muted de-karte-intro">{{ $intro }}</p>

    <div class="de-karte-grid">
        <div class="de-karte-svg-box">
            <svg class="de-karte-svg" viewBox="0 0 606 820" xmlns="http://www.w3.org/2000/svg"
                 role="img" aria-label="Interaktive Deutschlandkarte der Altkennzeichen nach Bundesland">
                @include('partials.de-map-paths')
            </svg>
            <div class="de-karte-legende" aria-hidden="true">
                <span class="muted">wenige</span>
                <span class="de-karte-leg-bar"></span>
                <span class="muted">viele Altkennzeichen</span>
            </div>
        </div>

        <aside class="de-karte-panel" data-de-panel aria-live="polite">
            <div class="de-karte-panel-leer" data-de-leer>
                <p class="muted">Wähle ein <strong>Bundesland</strong> auf der Karte.</p>
                <p class="muted" style="font-size:.86rem">Insgesamt
                    <strong>{{ array_sum($counts) }}</strong> wieder eingeführte Altkennzeichen.</p>
            </div>
            <div class="de-karte-panel-inhalt" data-de-inhalt hidden>
                <h3 class="de-karte-panel-titel" data-de-name></h3>
                <p class="muted de-karte-panel-meta" data-de-meta></p>
                <div class="de-karte-codes" data-de-codes></div>
                <a class="de-karte-panel-link" data-de-link href="#">Alle Zulassungsstellen →</a>
            </div>
        </aside>
    </div>
</div>

<script type="application/json" data-de-karte-daten>@json($karteJson)</script>

<script>
(function () {
    var root = document.querySelector('[data-de-karte]');
    if (!root || root.dataset.deBound) return;
    root.dataset.deBound = '1';

    var dataEl = document.querySelector('[data-de-karte-daten]');
    var KARTE = {};
    try { KARTE = JSON.parse(dataEl.textContent || '{}'); } catch (e) { KARTE = {}; }

    var max = parseInt(root.dataset.max, 10) || 1;
    var svg = root.querySelector('.de-karte-svg');
    var paths = svg ? svg.querySelectorAll('path[data-bl]') : [];
    var panel = root.querySelector('[data-de-panel]');
    var leer = panel.querySelector('[data-de-leer]');
    var inhalt = panel.querySelector('[data-de-inhalt]');
    var elName = panel.querySelector('[data-de-name]');
    var elMeta = panel.querySelector('[data-de-meta]');
    var elCodes = panel.querySelector('[data-de-codes]');
    var elLink = panel.querySelector('[data-de-link]');
    var base = '{{ url('/') }}';

    // Choropleth einfärben.
    paths.forEach(function (p) {
        var name = p.getAttribute('data-bl');
        var d = KARTE[name];
        var c = d ? d.count : 0;
        var t = max > 0 ? c / max : 0;
        // 0 -> sehr hell, 1 -> kräftiges Markenblau (#078ac5)
        var light = 92 - Math.round(t * 52); // 92% .. 40% Lightness
        p.style.fill = c > 0 ? 'hsl(199 92% ' + light + '%)' : 'var(--de-karte-leer, #e9eef2)';
        p.setAttribute('tabindex', '0');
        p.setAttribute('role', 'button');
        p.setAttribute('aria-label', name + (d ? ' – ' + d.count + ' Altkennzeichen' : ' – keine Altkennzeichen'));
        p.style.cursor = 'pointer';
    });

    function waehle(name) {
        var d = KARTE[name];
        paths.forEach(function (p) { p.classList.toggle('is-aktiv', p.getAttribute('data-bl') === name); });

        elName.textContent = name;
        if (!d || !d.count) {
            elMeta.textContent = 'Für dieses Bundesland sind aktuell keine wieder eingeführten Altkennzeichen erfasst.';
            elCodes.innerHTML = '';
            elLink.style.display = 'none';
        } else {
            elMeta.textContent = d.count + (d.count === 1 ? ' wieder eingeführtes Altkennzeichen' : ' wieder eingeführte Altkennzeichen');
            elCodes.innerHTML = d.codes.map(function (k) {
                var stadt = k.stadt ? ' title="' + String(k.stadt).replace(/"/g, '&quot;') + '"' : '';
                return '<a class="badge badge-alt" href="' + base + '/kennzeichen/' + k.slug + '"' + stadt + '>' + k.code + '</a>';
            }).join('');
            if (d.slug) {
                elLink.href = base + '/zulassungsstelle/' + d.slug;
                elLink.textContent = 'Zulassungsstellen in ' + name + ' →';
                elLink.style.display = '';
            } else {
                elLink.style.display = 'none';
            }
        }
        leer.hidden = true;
        inhalt.hidden = false;
    }

    paths.forEach(function (p) {
        var name = p.getAttribute('data-bl');
        p.addEventListener('click', function () { waehle(name); });
        p.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); waehle(name); }
        });
        p.addEventListener('mouseenter', function () { p.classList.add('is-hover'); });
        p.addEventListener('mouseleave', function () { p.classList.remove('is-hover'); });
    });
})();
</script>
