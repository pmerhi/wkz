<x-layout :title="$title" :description="$description" :canonical="$canonical" :schemas="$schemas">
    <nav class="breadcrumb">
        <a href="{{ url('/') }}">Start</a> ›
        <a href="{{ url('/kennzeichen') }}">Kennzeichen</a> › Quiz
    </nav>

    <section class="hero hero-sm reveal in">
        <h1>Kennzeichen-Quiz: Kürzel-Raten</h1>
        <p class="lead">3 Leben · 15 Sekunden pro Frage · je schneller, desto mehr Punkte –
        und der Schwierigkeitsgrad steigt!</p>
    </section>

    <section class="section reveal">
        <div class="quiz-layout">
            <div class="quiz-main" id="quiz"
                 data-pool='@json($pool)'
                 data-highscores='@json($highscores)'
                 data-score-url="{{ url('/kennzeichen-quiz/score') }}">

                {{-- Herausforderung (wenn über ?score= aufgerufen) --}}
                <div id="qChallenge" class="box box-info" hidden style="margin-bottom:16px"></div>

                {{-- Startbildschirm --}}
                <div id="qStart">
                    <p class="lead-intro">Welche Stadt oder welcher Landkreis steckt hinter dem
                        Kfz-Kennzeichen? Trag deinen Namen ein und leg los.</p>
                    <p style="margin-top:16px">
                        <input id="qName" class="quiz-name" type="text" maxlength="40" placeholder="Dein Spitzname" autocomplete="off">
                        <button class="cta" id="qBegin" type="button">Spiel starten →</button>
                    </p>
                    <p class="muted" style="font-size:.8rem;margin-top:2px">Dein Name erscheint öffentlich in der Hall of Fame – bitte einen Spitznamen, keinen echten Namen verwenden.</p>
                    <p style="margin-top:10px"><a class="btn" href="{{ url('/kennzeichen-quiz/hall-of-fame') }}">🏅 Hall of Fame ansehen</a></p>
                </div>

                {{-- Spielbildschirm --}}
                <div id="qPlay" hidden>
                    <div class="quiz-stats">
                        <span class="quiz-lives" id="qLives">❤️❤️❤️</span>
                        <span id="qScore">0 Punkte</span>
                    </div>
                    <div class="quiz-timer" id="qTimerWrap"><span id="qTimer"></span></div>

                    <div class="kfz-plate" aria-hidden="true">
                        <span class="kfz-eu"><span class="kfz-stars">∗</span><span class="kfz-d">D</span></span>
                        <span class="kfz-body"><b id="qCode">B</b>&nbsp;··&nbsp;····</span>
                    </div>
                    <h2 id="qFrage" style="margin-top:16px">Wofür steht dieses Kennzeichen?</h2>
                    <div class="quiz-opts" id="qOpts"></div>
                </div>

                {{-- Ende + Tagesbestenliste --}}
                <div id="qDone" hidden>
                    <h2 id="qFinal" style="text-align:center"></h2>
                    <p class="muted" id="qFinalSub" style="text-align:center"></p>
                    <p style="text-align:center;margin:8px 0 22px">
                        <button class="cta" id="qRestart" type="button">Nochmal spielen</button>
                        <button class="btn" id="qShare" type="button">🔗 Score teilen</button>
                        <a class="btn" href="{{ url('/kennzeichen-quiz/hall-of-fame') }}">🏅 Hall of Fame</a>
                        <span id="qShareMsg" class="muted" hidden>✓ In Zwischenablage kopiert!</span>
                    </p>

                    <h2>🏆 Tagesbestenliste</h2>
                    <table class="hs-table">
                        <thead><tr><th>#</th><th>Name</th><th style="text-align:right">Punkte</th><th style="text-align:right">Richtig</th></tr></thead>
                        <tbody id="hsBody"></tbody>
                    </table>
                </div>
            </div>

            {{-- Infos & Regeln (bebildert, neben dem Spiel) --}}
            <aside class="quiz-info reveal">
                <h2>Infos &amp; Regeln</h2>
                <ul class="quiz-rules">
                    <li><span class="ic">🔤</span><span>Du siehst die Stadtbuchstaben (z.&nbsp;B. <strong>M</strong>, <strong>B</strong>, <strong>HD</strong>).</span></li>
                    <li><span class="ic">✅</span><span>Wähle aus 4 Antworten den richtigen Zulassungsbezirk.</span></li>
                    <li><span class="ic">❤️</span><span>Du hast <strong>3 Leben</strong>. Falsche Antwort = 1 Leben weg.</span></li>
                    <li><span class="ic">⚡</span><span>Bis zu <strong>100 Punkte</strong> pro Frage – je schneller, desto mehr.</span></li>
                    <li><span class="ic">⏱️</span><span>Pro Frage <strong>15 Sekunden</strong>.</span></li>
                    <li><span class="ic">📈</span><span>Der Schwierigkeitsgrad steigt im Laufe des Spiels.</span></li>
                </ul>
            </aside>
        </div>
    </section>

    <script>
    (function () {
        var root = document.getElementById('quiz');
        var POOL = JSON.parse(root.getAttribute('data-pool') || '[]');   // bereits leicht→schwer sortiert
        var highscores = JSON.parse(root.getAttribute('data-highscores') || '{}');
        var scoreUrl = root.getAttribute('data-score-url');
        if (POOL.length < 4) return;

        var LIMIT = 15;
        var elStart = document.getElementById('qStart'), elPlay = document.getElementById('qPlay'),
            elDone = document.getElementById('qDone'), elCode = document.getElementById('qCode'),
            elOpts = document.getElementById('qOpts'), elScore = document.getElementById('qScore'),
            elLives = document.getElementById('qLives'), elTimer = document.getElementById('qTimer'),
            elTimerWrap = document.getElementById('qTimerWrap'), elName = document.getElementById('qName');

        var lives, score, richtige, playerName, locked, startTs, timer, qi, order;

        // Herausforderung aus ?score= (zu überholender Wert)
        var ziel = parseInt(new URLSearchParams(location.search).get('score'), 10);
        if (isNaN(ziel) || ziel < 0) ziel = null;
        (function showChallenge() {
            if (ziel == null) return;
            var el = document.getElementById('qChallenge');
            el.innerHTML = '🎯 <strong>Du wurdest herausgefordert!</strong> Schlage <strong>' + ziel + ' Punkte</strong> im Kennzeichen-Quiz.';
            el.hidden = false;
        })();

        function shuffle(a) { for (var i = a.length - 1; i > 0; i--) { var j = Math.floor(Math.random() * (i + 1)); var t = a[i]; a[i] = a[j]; a[j] = t; } return a; }

        // Reihenfolge: Schwierigkeit bleibt grob erhalten, aber Blöcke von 4 werden gemischt (Variation pro Spiel).
        function spielreihenfolge() {
            var idx = POOL.map(function (_, i) { return i; });
            for (var i = 0; i < idx.length; i += 4) {
                var blk = idx.slice(i, i + 4); shuffle(blk);
                for (var j = 0; j < blk.length; j++) idx[i + j] = blk[j];
            }
            return idx;
        }

        function buildQuestion() {
            var item = POOL[order[qi % order.length]];
            // Ablenker aus ähnlichem Schwierigkeitsfenster (≈ gleiche Bekanntheit) wählen.
            var pos = qi % order.length, W = 70;
            var von = Math.max(0, pos - W), bis = Math.min(POOL.length, pos + W);
            var fenster = []; for (var p = von; p < bis; p++) fenster.push(POOL[p]);
            var falsch = shuffle(fenster.filter(function (x) { return x.antwort !== item.antwort; }).map(function (x) { return x.antwort; }));
            var opts = [], seen = {};
            for (var c = 0; c < falsch.length && opts.length < 3; c++) { if (!seen[falsch[c]]) { seen[falsch[c]] = 1; opts.push(falsch[c]); } }
            // Auffüllen aus dem ganzen Pool, falls Fenster zu klein
            for (var d = 0; opts.length < 3 && d < POOL.length; d++) { var a = POOL[d].antwort; if (a !== item.antwort && !seen[a]) { seen[a] = 1; opts.push(a); } }
            opts.push(item.antwort);
            qi++;
            return { code: item.code, antwort: item.antwort, optionen: shuffle(opts) };
        }

        function renderLives() { elLives.textContent = '❤️'.repeat(lives) + '🖤'.repeat(3 - lives); }

        function nextQuestion() {
            locked = false;
            var q = buildQuestion();
            elCode.textContent = q.code;
            elOpts.innerHTML = '';
            q.optionen.forEach(function (opt) {
                var b = document.createElement('button');
                b.className = 'quiz-opt'; b.type = 'button'; b.textContent = opt;
                b.addEventListener('click', function () { answer(b, opt, q.antwort); });
                elOpts.appendChild(b);
            });
            startTs = Date.now();
            startTimer();
        }

        function startTimer() {
            elTimerWrap.classList.remove('warn');
            if (timer) clearInterval(timer);
            timer = setInterval(function () {
                var el = (Date.now() - startTs) / 1000;
                elTimer.style.width = Math.max(0, 100 - (el / LIMIT) * 100) + '%';
                if (el >= LIMIT - 5) elTimerWrap.classList.add('warn');
                if (el >= LIMIT) timeout();
            }, 100);
        }

        function punkte(elapsedSec) { return Math.max(1, Math.round(100 - (elapsedSec / (LIMIT - 1)) * 99)); }

        function reveal(correct, chosenBtn) {
            elOpts.querySelectorAll('.quiz-opt').forEach(function (b) {
                b.disabled = true;
                if (b.textContent === correct) b.classList.add('correct');
            });
            if (chosenBtn && chosenBtn.textContent !== correct) chosenBtn.classList.add('wrong');
        }

        function answer(btn, opt, correct) {
            if (locked) return; locked = true; clearInterval(timer);
            var el = (Date.now() - startTs) / 1000;
            reveal(correct, btn);
            if (opt === correct) { score += punkte(el); richtige++; elScore.textContent = score + ' Punkte'; }
            else { lives--; renderLives(); }
            setTimeout(after, 800);
        }

        function timeout() {
            if (locked) return; locked = true; clearInterval(timer);
            lives--; renderLives(); reveal(null, null);
            setTimeout(after, 800);
        }

        function after() { if (lives <= 0) finish(); else nextQuestion(); }

        function finish() {
            elPlay.hidden = true; elDone.hidden = false;
            document.getElementById('qFinal').textContent = score + ' Punkte';
            var sub = richtige + ' Fragen richtig beantwortet.';
            if (ziel != null) {
                sub += score > ziel
                    ? ' 🎉 Herausforderung von ' + ziel + ' Punkten geknackt!'
                    : ' Die Herausforderung (' + ziel + ' Punkte) hast du knapp verpasst.';
            }
            document.getElementById('qFinalSub').textContent = sub;
            save();
        }

        function teilen() {
            var url = location.origin + location.pathname + '?score=' + score;
            var text = 'Ich habe ' + score + ' Punkte im Kennzeichen-Quiz geholt! ' + url;
            var msg = document.getElementById('qShareMsg');
            function done() { if (msg) { msg.hidden = false; setTimeout(function () { msg.hidden = true; }, 2500); } }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done, fallback);
            } else { fallback(); }
            function fallback() {
                var ta = document.createElement('textarea');
                ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
                document.body.appendChild(ta); ta.focus(); ta.select();
                try { document.execCommand('copy'); } catch (e) {}
                document.body.removeChild(ta); done();
            }
        }
        document.getElementById('qShare').addEventListener('click', teilen);

        function save() {
            var token = document.querySelector('meta[name="csrf-token"]');
            fetch(scoreUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token ? token.content : '' },
                body: JSON.stringify({ name: playerName, score: score, richtige: richtige })
            }).then(function (r) { return r.json(); }).then(function (d) {
                if (d && d.highscores) highscores = d.highscores;
                renderHighscores();
            }).catch(renderHighscores);
        }

        function renderHighscores() {
            var list = (highscores && highscores.tag) || [];
            var body = document.getElementById('hsBody');
            body.innerHTML = '';
            if (!list.length) { body.innerHTML = '<tr><td colspan="4" class="muted">Noch keine Einträge heute – sei der Erste!</td></tr>'; return; }
            list.forEach(function (e, idx) {
                var tr = document.createElement('tr');
                if (e.name === playerName && e.score === score) tr.className = 'me';
                tr.innerHTML = '<td>' + (idx + 1) + '</td><td>' + esc(e.name) + '</td><td class="num">' + e.score + '</td><td class="num">' + e.richtige + '</td>';
                body.appendChild(tr);
            });
        }
        function esc(s) { return (s + '').replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

        function start() {
            playerName = (elName.value || '').trim().slice(0, 40) || 'Anonym';
            lives = 3; score = 0; richtige = 0; qi = 0; order = spielreihenfolge();
            renderLives(); elScore.textContent = '0 Punkte';
            elStart.hidden = true; elDone.hidden = true; elPlay.hidden = false;
            nextQuestion();
        }
        document.getElementById('qBegin').addEventListener('click', start);
        elName.addEventListener('keydown', function (e) { if (e.key === 'Enter') start(); });
        document.getElementById('qRestart').addEventListener('click', function () { elDone.hidden = true; elStart.hidden = false; });
    })();
    </script>
</x-layout>
