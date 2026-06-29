@props(['code' => null])
{{-- Spielerischer Einstieg ins Kennzeichen-Quiz; auf Kennzeichen-bezogenen Seiten eingebunden. --}}
<section class="section reveal">
    <div class="feature">
        <span class="tag-new">Quiz</span>
        <h2>Kennst du die Kfz-Kürzel?</h2>
        <p class="lead-intro">
            @if($code)
                Du weißt, dass <strong>{{ strtoupper($code) }}</strong> für deine Region steht – aber kennst du auch die anderen?
            @endif
            Rate im <strong>Kennzeichen-Quiz</strong>, welche Stadt hinter dem Kürzel steckt: 3 Leben, Punkte fürs Tempo,
            Hall of Fame – und <strong>fordere Freunde heraus</strong>.
        </p>
        <p style="margin-top:16px">
            <a class="cta" href="{{ url('/kennzeichen-quiz') }}">🎮 Quiz starten →</a>
            <a class="btn" href="{{ url('/kennzeichen-quiz/hall-of-fame') }}">🏅 Hall of Fame</a>
        </p>
    </div>
</section>
