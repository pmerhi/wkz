<x-layout :title="$title" :description="$description" :canonical="$canonical" :robots="$robots ?? 'noindex,follow'" :schemas="$schemas">
    <nav class="breadcrumb">
        <a href="{{ url('/') }}">Start</a> ›
        <a href="{{ url('/kennzeichen') }}">Kennzeichen</a> ›
        <a href="{{ url('/kennzeichen-quiz') }}">Quiz</a> › Hall of Fame
    </nav>

    <section class="hero hero-sm reveal in">
        <h1>🏅 Hall of Fame</h1>
        <p class="lead">Die besten Kennzeichen-Rater – Top 50 von heute, dieser Woche, diesem Monat und insgesamt.</p>
    </section>

    <section class="section reveal">
        <div class="hs-tabs" id="rlTabs">
            @foreach($zeit as $key => $label)
                <button class="hs-tab {{ $loop->first ? 'active' : '' }}" data-pane="{{ $key }}" type="button">{{ $label }}</button>
            @endforeach
        </div>

        @foreach($zeit as $key => $label)
            <div class="rl-pane" data-pane="{{ $key }}" @unless($loop->first) hidden @endunless>
                <table class="hs-table">
                    <thead><tr>
                        <th>#</th><th>Name</th>
                        <th style="text-align:right">Punkte</th>
                        <th style="text-align:right">Richtig</th>
                        <th>Datum</th>
                    </tr></thead>
                    <tbody>
                        @forelse($listen[$key] as $i => $e)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $e['name'] }}</td>
                                <td class="num">{{ number_format($e['score'], 0, ',', '.') }}</td>
                                <td class="num">{{ $e['richtige'] }}</td>
                                <td class="muted">{{ $e['datum'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="muted">Noch keine Einträge in diesem Zeitraum.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endforeach

        <p style="margin-top:20px"><a class="btn" href="{{ url('/kennzeichen-quiz') }}">← Zurück zum Quiz</a></p>
    </section>

    <script>
    (function () {
        var tabs = document.querySelectorAll('#rlTabs .hs-tab');
        var panes = document.querySelectorAll('.rl-pane');
        tabs.forEach(function (t) {
            t.addEventListener('click', function () {
                var p = t.getAttribute('data-pane');
                tabs.forEach(function (x) { x.classList.toggle('active', x === t); });
                panes.forEach(function (pane) { pane.hidden = pane.getAttribute('data-pane') !== p; });
            });
        });
    })();
    </script>
</x-layout>
