@props(['kuerzel' => null])
@php
    $code = strtoupper(trim((string) $kuerzel));
    $kombis = collect(config('kennzeichen_woerter', []))
        ->map(fn ($w) => strtoupper($w))->unique()
        ->filter(fn ($w) => strlen($w) > strlen($code) && str_starts_with($w, $code) && strlen($w) - strlen($code) <= 2)
        ->map(fn ($w) => substr($w, strlen($code)))
        ->unique()->take(8)->values();
@endphp
@if($code !== '')
    <section class="section reveal" id="generator">
        <h2>Wunschkennzeichen-Generator für {{ $code }}</h2>
        <p class="lead-intro">Tippe deine Buchstaben und Zahlen – und sieh sofort, wie dein
            Kennzeichen aussieht.</p>

        <div class="kfz-plate" aria-hidden="true">
            <span class="kfz-eu"><span class="kfz-stars">∗</span><span class="kfz-d">D</span></span>
            <span class="kfz-body"><b>{{ $code }}</b>&nbsp;<span class="js-gen-let">MAX</span>&nbsp;<span class="js-gen-num">123</span></span>
        </div>

        <div class="gen-controls">
            <label>Buchstaben<br><input class="js-gen-in-let" type="text" maxlength="2" placeholder="z. B. AB" autocomplete="off"></label>
            <label>Zahlen<br><input class="js-gen-in-num" type="text" maxlength="4" inputmode="numeric" placeholder="z. B. 123" autocomplete="off"></label>
        </div>

        @if($kombis->isNotEmpty())
            <p class="muted" style="margin:16px 0 4px">Beliebte Kombis für {{ $code }} – zum Übernehmen antippen:</p>
            <p>@foreach($kombis as $rest)<a class="badge js-gen-kombi" role="button" data-let="{{ $rest }}">{{ $code }}-{{ $rest }}</a>@endforeach</p>
        @endif

        <p style="margin-top:16px"><x-reservierung-cta :label="'generator:'.$code" campaign="generator" /></p>
    </section>

    <script>
    (function () {
        var sec = document.getElementById('generator');
        if (!sec) return;
        var outL = sec.querySelector('.js-gen-let'), outN = sec.querySelector('.js-gen-num');
        var inL = sec.querySelector('.js-gen-in-let'), inN = sec.querySelector('.js-gen-in-num');
        function render() {
            var l = (inL.value || '').toUpperCase().replace(/[^A-ZÄÖÜ]/g, '').slice(0, 2);
            var n = (inN.value || '').replace(/\D/g, '').slice(0, 4);
            inL.value = l; inN.value = n;
            outL.textContent = l || 'MAX';
            outN.textContent = n || '123';
        }
        inL.addEventListener('input', render);
        inN.addEventListener('input', render);
        sec.querySelectorAll('.js-gen-kombi').forEach(function (b) {
            b.addEventListener('click', function () {
                inL.value = b.getAttribute('data-let') || '';
                if (!inN.value) inN.value = (new Date().getFullYear() % 100).toString();
                render(); inN.focus();
            });
        });
    })();
    </script>
@endif
