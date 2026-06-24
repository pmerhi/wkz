<x-layout :title="$title" :description="$description" :canonical="$canonical" :schemas="$schemas">
    <nav class="breadcrumb">
        <a href="{{ url('/') }}">Start</a> ›
        <a href="{{ url('/kennzeichen') }}">Kennzeichen</a> › Quiz
    </nav>

    <section class="hero hero-sm reveal in">
        <h1>Kennzeichen-Quiz: Kürzel-Raten</h1>
        <p class="lead">Welche Stadt oder welcher Landkreis steckt hinter dem Kfz-Kennzeichen?
        15 Fragen – schaffst du den Highscore?</p>
    </section>

    <section class="section reveal" id="quiz" data-fragen='@json($fragen)'>
        <div class="quiz-head">
            <span id="qProgress">Frage 1</span>
            <span id="qScore">0 Punkte</span>
            <span>🏆 Highscore: <span id="qHigh">0</span></span>
        </div>

        <div id="qPlay">
            <div class="kfz-plate" aria-hidden="true">
                <span class="kfz-eu"><span class="kfz-stars">∗</span><span class="kfz-d">D</span></span>
                <span class="kfz-body"><b id="qCode">B</b>&nbsp;··&nbsp;····</span>
            </div>
            <h2 id="qFrage" style="margin-top:16px">Wofür steht dieses Kennzeichen?</h2>
            <div class="quiz-opts" id="qOpts"></div>
        </div>

        <div id="qDone" hidden style="text-align:center">
            <h2 id="qFinal"></h2>
            <p class="muted" id="qFinalSub"></p>
            <p style="margin-top:18px">
                <button class="btn" id="qRestart" type="button">Nochmal spielen</button>
                <a class="cta" href="{{ url('/kennzeichen') }}">Alle Kennzeichen ansehen →</a>
            </p>
        </div>
    </section>

    <script>
    (function () {
        var root = document.getElementById('quiz');
        var fragen = JSON.parse(root.getAttribute('data-fragen') || '[]');
        if (!fragen.length) return;
        var i = 0, score = 0, locked = false;
        var elCode = document.getElementById('qCode'), elFrage = document.getElementById('qFrage'),
            elOpts = document.getElementById('qOpts'), elProg = document.getElementById('qProgress'),
            elScore = document.getElementById('qScore'), elHigh = document.getElementById('qHigh'),
            elPlay = document.getElementById('qPlay'), elDone = document.getElementById('qDone');

        function high(v) { try { if (v !== undefined) localStorage.setItem('kfzQuizHigh', v); return +localStorage.getItem('kfzQuizHigh') || 0; } catch (e) { return 0; } }
        elHigh.textContent = high();

        function render() {
            locked = false;
            var f = fragen[i];
            elCode.textContent = f.code;
            elProg.textContent = 'Frage ' + (i + 1) + '/' + fragen.length;
            elScore.textContent = score + ' Punkte';
            elOpts.innerHTML = '';
            f.optionen.forEach(function (opt) {
                var b = document.createElement('button');
                b.className = 'quiz-opt'; b.type = 'button'; b.textContent = opt;
                b.addEventListener('click', function () { answer(b, opt, f.antwort); });
                elOpts.appendChild(b);
            });
        }
        function answer(btn, opt, correct) {
            if (locked) return; locked = true;
            var buttons = elOpts.querySelectorAll('.quiz-opt');
            buttons.forEach(function (b) {
                b.disabled = true;
                if (b.textContent === correct) b.classList.add('correct');
            });
            if (opt === correct) { score++; elScore.textContent = score + ' Punkte'; }
            else btn.classList.add('wrong');
            setTimeout(next, 850);
        }
        function next() {
            i++;
            if (i >= fragen.length) return finish();
            render();
        }
        function finish() {
            elPlay.hidden = true; elDone.hidden = false;
            var best = high();
            if (score > best) { high(score); best = score; elHigh.textContent = best; }
            document.getElementById('qFinal').textContent = score + ' von ' + fragen.length + ' richtig!';
            document.getElementById('qFinalSub').textContent = score === fragen.length
                ? 'Perfekt – du bist ein echter Kennzeichen-Profi!' : 'Highscore: ' + best + '. Probier es gleich nochmal.';
        }
        document.getElementById('qRestart').addEventListener('click', function () {
            i = 0; score = 0; elDone.hidden = true; elPlay.hidden = false; render();
        });
        render();
    })();
    </script>
</x-layout>
