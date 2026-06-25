@props(['kuerzel' => null])
@php
    $code = strtoupper(trim((string) $kuerzel));
    $kombis = collect(config('kennzeichen_woerter', []))
        ->map(fn ($w) => strtoupper($w))->unique()
        ->filter(fn ($w) => strlen($w) > strlen($code) && str_starts_with($w, $code) && strlen($w) - strlen($code) <= 2)
        ->map(fn ($w) => substr($w, strlen($code)))
        ->unique()->take(8)->values();
    // Getrackter Funnel-Start; JS hängt symbol/letters/numbers an, Controller baut cId + kennzeichen.
    $goBase = url('/go/reservierung').'?'.http_build_query(['c' => 'generator', 'label' => 'generator:'.$code, 'v' => $ab['cta_text'] ?? 'a']);
@endphp
@if($code !== '')
    <section class="section reveal" id="generator" data-code="{{ $code }}" data-go="{{ $goBase }}">
        <h2>Wunschkennzeichen {{ $code }} prüfen &amp; reservieren</h2>
        <p class="lead-intro">Gib Buchstaben und Zahlen ein – wir prüfen live, ob die Kombination möglich ist.
            Tipp: Ein <strong>?</strong> dient als Platzhalter für ein einzelnes Zeichen.</p>

        <div class="kfz-plate" aria-hidden="true">
            <span class="kfz-eu"><span class="kfz-stars">∗</span><span class="kfz-d">D</span></span>
            <span class="kfz-body"><b>{{ $code }}</b>&nbsp;<span class="js-gen-let">MAX</span>&nbsp;<span class="js-gen-num">123</span></span>
        </div>

        <div class="gen-controls">
            <label>Buchstaben<br><input class="js-gen-in-let" type="text" maxlength="2" placeholder="z. B. SC" autocomplete="off"></label>
            <label>Zahlen<br><input class="js-gen-in-num" type="text" maxlength="4" inputmode="numeric" placeholder="z. B. 22" autocomplete="off"></label>
        </div>

        <p class="gen-status js-gen-status" aria-live="polite"></p>

        @if($kombis->isNotEmpty())
            <p class="muted" style="margin:8px 0 4px">Beliebte Kombis für {{ $code }} – zum Übernehmen antippen:</p>
            <p>@foreach($kombis as $rest)<a class="badge js-gen-kombi" role="button" data-let="{{ $rest }}">{{ $code }}-{{ $rest }}</a>@endforeach</p>
        @endif

        <p style="margin-top:16px"><a class="cta js-reservierung-cta js-gen-cta is-disabled" data-label="generator:{{ $code }}" rel="nofollow" aria-disabled="true" href="#">Jetzt Verfügbarkeit prüfen →</a></p>
    </section>

    <script>
    (function () {
        var sec = document.getElementById('generator');
        if (!sec) return;
        var CODE = sec.getAttribute('data-code') || '', GO = sec.getAttribute('data-go') || '';
        var FORBIDDEN = ['SS', 'SA', 'NS', 'KZ', 'HJ', 'SD'];   // bundesweit unzulässige Buchstabenpaare
        var outL = sec.querySelector('.js-gen-let'), outN = sec.querySelector('.js-gen-num');
        var inL = sec.querySelector('.js-gen-in-let'), inN = sec.querySelector('.js-gen-in-num');
        var status = sec.querySelector('.js-gen-status'), cta = sec.querySelector('.js-gen-cta');

        function disable(msg, isErr) {
            status.textContent = msg || '';
            status.className = 'gen-status js-gen-status' + (isErr ? ' err' : '');
            cta.classList.add('is-disabled'); cta.setAttribute('aria-disabled', 'true'); cta.href = '#';
        }

        function check() {
            var l = (inL.value || '').toUpperCase().replace(/[^A-Z?]/g, '').slice(0, 2);
            var n = (inN.value || '').replace(/[^0-9?]/g, '').slice(0, 4);
            inL.value = l; inN.value = n;
            outL.textContent = l || 'MAX'; outN.textContent = n || '123';

            if (!l && !n) { return disable('Buchstaben und Zahlen eingeben.', false); }

            // Plausibilität nach den deutschen Kennzeichen-Regeln
            if (l.length < 1) return disable('Bitte 1–2 Buchstaben eingeben.', true);
            if (n.length < 1) return disable('Bitte 1–4 Zahlen eingeben.', true);
            if (n.indexOf('?') < 0 && /^0/.test(n)) return disable('Die Zahl darf nicht mit 0 beginnen.', true);
            if (/^0+$/.test(n)) return disable('Die Zahl darf nicht 0 sein.', true);
            if ((CODE.length + l.length + n.length) > 8) return disable('Insgesamt sind höchstens 8 Zeichen erlaubt.', true);
            if (l.length === 2 && l.indexOf('?') < 0 && FORBIDDEN.indexOf(l) >= 0) return disable('Die Buchstaben „' + l + '" sind nicht zulässig.', true);

            status.textContent = '✓ Mögliche Kombination ' + CODE + '-' + l + '-' + n + ' – jetzt Verfügbarkeit prüfen.';
            status.className = 'gen-status js-gen-status ok';
            cta.classList.remove('is-disabled'); cta.removeAttribute('aria-disabled');
            cta.href = GO + '&symbol=' + encodeURIComponent(CODE) + '&letters=' + encodeURIComponent(l.toLowerCase()) + '&numbers=' + encodeURIComponent(n);
        }

        inL.addEventListener('input', check);
        inN.addEventListener('input', check);
        cta.addEventListener('click', function (e) { if (cta.classList.contains('is-disabled')) e.preventDefault(); });
        sec.querySelectorAll('.js-gen-kombi').forEach(function (b) {
            b.addEventListener('click', function () {
                inL.value = b.getAttribute('data-let') || '';
                if (!inN.value) inN.value = (new Date().getFullYear() % 100).toString();
                check(); inN.focus();
            });
        });
        check();
    })();
    </script>
@endif
