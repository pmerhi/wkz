@props([
    'titel' => 'Altkennzeichen-Infografik: interaktive Deutschlandkarte',
    'intro' => 'Klicke auf ein Bundesland, um hineinzuzoomen. Dann erscheinen die Landkreise mit ihren wieder eingeführten Altkennzeichen. Über den Button „zurück" gelangst du wieder zur Gesamtansicht.',
])
<figure class="ak-infografik reveal">
    <figcaption class="ak-infografik-cap">
        <h2 class="ak-infografik-titel">{{ $titel }}</h2>
        <p class="muted ak-infografik-intro">{{ $intro }}</p>
    </figcaption>
    <div class="ak-infografik-frame">
        <iframe src="{{ url('/infografik/index.html') }}"
                title="Interaktive Altkennzeichen-Karte Deutschlands"
                loading="lazy" scrolling="no"
                width="462" height="840"></iframe>
    </div>
</figure>
