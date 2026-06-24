<x-layout :title="$title" :description="$description" :canonical="$canonical" :schemas="$schemas">
    <nav class="breadcrumb">
        <a href="{{ url('/') }}">Start</a> ›
        <a href="{{ url('/kennzeichen') }}">Kennzeichen</a> › Quiz
    </nav>

    <section class="hero hero-sm reveal in">
        <h1>Kennzeichen-Quiz: Kürzel-Raten</h1>
        <p class="lead">3 Leben · 15 Sekunden pro Frage · je schneller, desto mehr Punkte.
        Trag dich in die Bestenliste ein!</p>
    </section>

    <section class="section reveal" id="quiz"
             data-pool='@json($pool)'
             data-highscores='@json($highscores)'
             data-score-url="{{ url('/kennzeichen-quiz/score') }}">

        {{-- Startbildschirm --}}
        <div id="qStart">
            <p class="lead-intro">Welche Stadt oder welcher Landkreis steckt hinter dem Kfz-Kennzeichen?
                Antworte so schnell wie möglich – bei <strong>0&nbsp;Sekunden gibt es 100&nbsp;Punkte</strong>,
                kurz vor Schluss nur noch 1. Drei falsche oder zu langsame Antworten und das Spiel ist vorbei.</p>
            <p style="margin-top:16px">
                <input id="qName" class="quiz-name" type="text" maxlength="40" placeholder="Dein Name" autocomplete="off">
                <button class="cta" id="qBegin" type="button">Spiel starten →</button>
            </p>
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

        {{-- Ende + Highscores --}}
        <div id="qDone" hidden>
            <h2 id="qFinal" style="text-align:center"></h2>
            <p class="muted" id="qFinalSub" style="text-align:center"></p>
            <p style="text-align:center;margin:8px 0 22px"><button class="cta" id="qRestart" type="button">Nochmal spielen</button></p>

            <h2>🏆 Bestenliste</h2>
            <div class="hs-tabs" id="hsTabs">
                <button class="hs-tab active" data-z="tag" type="button">Heute</button>
                <button class="hs-tab" data-z="woche" type="button">Woche</button>
                <button class="hs-tab" data-z="monat" type="button">Monat</button>
                <button class="hs-tab" data-z="jahr" type="button">Jahr</button>
                <button class="hs-tab" data-z="gesamt" type="button">Gesamt</button>
            </div>
            <table class="hs-table">
                <thead><tr><th>#</th><th>Name</th><th style="text-align:right">Punkte</th><th style="text-align:right">Richtig</th></tr></thead>
                <tbody id="hsBody"></tbody>
            </table>
        </div>
    </section>

    <script>
    (function () {
        var root = document.getElementById('quiz');
        var POOL = JSON.parse(root.getAttribute('data-pool') || '[]');
        var highscores = JSON.parse(root.getAttribute('data-highscores') || '{}');
        var scoreUrl = root.getAttribute('data-score-url');
        if (POOL.length < 4) return;

        var LIMIT = 15;            // Sekunden pro Frage
        var elStart = document.getElementById('qStart'), elPlay = document.getElementById('qPlay'),
            elDone = document.getElementById('qDone'), elCode = document.getElementById('qCode'),
            elOpts = document.getElementById('qOpts'), elScore = document.getElementById('qScore'),
            elLives = document.getElementById('qLives'), elTimer = document.getElementById('qTimer'),
            elTimerWrap = document.getElementById('qTimerWrap'), elName = document.getElementById('qName');

        var lives, score, richtige, playerName, locked, startTs, timer, recent = [];

        function shuffle(a) { for (var i = a.length - 1; i > 0; i--) { var j = Math.floor(Math.random() * (i + 1)); var t = a[i]; a[i] = a[j]; a[j] = t; } return a; }

        function buildQuestion() {
            // nicht zuletzt gezeigte Codes bevorzugen
            var item; var guard = 0;
            do { item = POOL[Math.floor(Math.random() * POOL.length)]; guard++; } while (recent.indexOf(item.code) !== -1 && guard < 30);
            recent.push(item.code); if (recent.length > 20) recent.shift();
            var falsch = shuffle(POOL.filter(function (p) { return p.antwort !== item.antwort; }).map(function (p) { return p.antwort; }));
            var opts = []; var seen = {};
            for (var k = 0; k < falsch.length && opts.length < 3; k++) { if (!seen[falsch[k]]) { seen[falsch[k]] = 1; opts.push(falsch[k]); } }
            opts.push(item.antwort);
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
                var pct = Math.max(0, 100 - (el / LIMIT) * 100);
                elTimer.style.width = pct + '%';
                if (el >= LIMIT - 5) elTimerWrap.classList.add('warn');
                if (el >= LIMIT) timeout();
            }, 100);
        }

        function punkte(elapsedSec) {
            return Math.max(1, Math.round(100 - (elapsedSec / (LIMIT - 1)) * 99));
        }

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
            if (opt === correct) { var p = punkte(el); score += p; richtige++; elScore.textContent = score + ' Punkte'; }
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
            document.getElementById('qFinalSub').textContent = richtige + ' Fragen richtig beantwortet.';
            save();
        }

        function save() {
            var token = document.querySelector('meta[name="csrf-token"]');
            fetch(scoreUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token ? token.content : '' },
                body: JSON.stringify({ name: playerName, score: score, richtige: richtige })
            }).then(function (r) { return r.json(); }).then(function (d) {
                if (d && d.highscores) highscores = d.highscores;
                renderHighscores(activeZeitraum);
            }).catch(function () { renderHighscores(activeZeitraum); });
        }

        var activeZeitraum = 'tag';
        function renderHighscores(z) {
            activeZeitraum = z;
            document.querySelectorAll('.hs-tab').forEach(function (t) { t.classList.toggle('active', t.getAttribute('data-z') === z); });
            var list = (highscores && highscores[z]) || [];
            var body = document.getElementById('hsBody');
            body.innerHTML = '';
            if (!list.length) { body.innerHTML = '<tr><td colspan="4" class="muted">Noch keine Einträge – sei der Erste!</td></tr>'; return; }
            list.forEach(function (e, idx) {
                var tr = document.createElement('tr');
                if (e.name === playerName && e.score === score) tr.className = 'me';
                tr.innerHTML = '<td>' + (idx + 1) + '</td><td>' + esc(e.name) + '</td><td class="num">' + e.score + '</td><td class="num">' + e.richtige + '</td>';
                body.appendChild(tr);
            });
        }
        function esc(s) { return (s + '').replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

        document.querySelectorAll('.hs-tab').forEach(function (t) {
            t.addEventListener('click', function () { renderHighscores(t.getAttribute('data-z')); });
        });

        function start() {
            playerName = (elName.value || '').trim().slice(0, 40) || 'Anonym';
            lives = 3; score = 0; richtige = 0; recent = [];
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
