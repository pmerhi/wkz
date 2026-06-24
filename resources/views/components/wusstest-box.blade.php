@php
    // Zeigt nur freigegebene Fakten (im Admin auf "umsetzen"/"umgesetzt" gesetzt).
    $fakten = \App\Models\WusstestFakt::whereIn('status', ['umsetzen', 'umgesetzt'])
        ->inRandomOrder()->limit(8)->get(['titel', 'beschreibung', 'quelle']);
@endphp
@if($fakten->isNotEmpty())
    <section class="section reveal">
        <div class="wusstest-box" data-fakten='@json($fakten->map(fn ($f) => ['t' => $f->titel, 'b' => $f->beschreibung, 'q' => $f->quelle])->values())'>
            <div class="wusstest-head">💡 Wusstest du?</div>
            <h3 class="wusstest-titel js-wt-titel"></h3>
            <p class="wusstest-text js-wt-text"></p>
            <div class="wusstest-foot">
                <a class="wusstest-quelle js-wt-quelle" href="#" target="_blank" rel="nofollow noopener" hidden>Quelle ansehen ↗</a>
                <button type="button" class="wusstest-next js-wt-next">Nächster Fakt →</button>
            </div>
        </div>
    </section>
    @once
        <script>
        (function () {
            document.querySelectorAll('.wusstest-box').forEach(function (box) {
                var fakten; try { fakten = JSON.parse(box.getAttribute('data-fakten') || '[]'); } catch (e) { fakten = []; }
                if (!fakten.length) return;
                var i = 0,
                    t = box.querySelector('.js-wt-titel'), p = box.querySelector('.js-wt-text'),
                    q = box.querySelector('.js-wt-quelle'), n = box.querySelector('.js-wt-next');
                function render() {
                    var f = fakten[i % fakten.length];
                    t.textContent = f.t || ''; p.textContent = f.b || '';
                    if (f.q) { q.href = f.q; q.hidden = false; } else { q.hidden = true; }
                }
                if (fakten.length < 2) n.style.display = 'none';
                n.addEventListener('click', function () { i++; render(); });
                render();
            });
        })();
        </script>
    @endonce
@endif
