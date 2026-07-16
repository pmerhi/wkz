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
        <iframe id="ak-infografik-iframe" src="{{ url('/infografik/index.html') }}"
                title="Interaktive Altkennzeichen-Karte Deutschlands"
                loading="lazy" scrolling="no"></iframe>
    </div>
</figure>
<script>
// Altkennzeichen-Infografik: iframe (same-origin) auf Inhaltshöhe bringen,
// damit die volle Breite ohne Scrollbalken/Abschnitt dargestellt wird.
(function(){
    var f = document.getElementById('ak-infografik-iframe');
    if (!f) return;
    function fit(){
        try {
            var doc = f.contentDocument || f.contentWindow.document;
            var h = Math.max(
                doc.documentElement.scrollHeight,
                doc.body ? doc.body.scrollHeight : 0
            );
            if (h) f.style.height = h + 'px';
        } catch (e) {}
    }
    f.addEventListener('load', function(){
        fit();
        // Zoom/Checkbox im iframe ändert die Höhe -> nachmessen
        try { f.contentDocument.addEventListener('click', function(){ setTimeout(fit, 80); }); } catch (e) {}
    });
    window.addEventListener('resize', function(){ clearTimeout(f._t); f._t = setTimeout(fit, 120); });
    // Fallback, falls 'load' vor Skriptausführung feuerte
    setTimeout(fit, 400);
})();
</script>
