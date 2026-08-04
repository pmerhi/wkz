@php
    $bew = config('abmeldung.bewertung');
@endphp
<x-layout :title="$title" :description="$description" :canonical="$canonical" :schemas="$schemas">
    <nav class="breadcrumb"><a href="{{ url('/') }}">Start</a> › Kfz-Abmeldung</nav>

    <style>
        .abm-schritte{list-style:none;counter-reset:s;margin:0;padding:0;display:grid;gap:14px;
            grid-template-columns:repeat(auto-fit,minmax(240px,1fr))}
        .abm-schritte li{counter-increment:s;position:relative;background:var(--bg);border:1px solid var(--line);
            border-radius:12px;padding:18px 18px 18px 58px;box-shadow:var(--shadow)}
        .abm-schritte li::before{content:counter(s);position:absolute;left:16px;top:16px;width:28px;height:28px;
            border-radius:50%;background:var(--ok);color:#fff;font-weight:800;display:flex;align-items:center;justify-content:center}
        .abm-schritte strong{display:block;margin-bottom:3px;color:var(--ink)}
        .abm-need{display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr))}
        .abm-need>div{background:var(--bg);border:1px solid var(--line);border-radius:12px;padding:16px 18px;box-shadow:var(--shadow)}
        .abm-need strong{display:block;margin-bottom:4px;color:var(--ink)}
        .abm-need p{margin:0;font-size:.92rem;color:var(--mut)}
        .abm-nutzen{display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));margin-top:6px}
        .abm-nutzen>div{background:var(--bg);border:1px solid var(--line);border-radius:12px;padding:18px 20px;box-shadow:var(--shadow)}
        .abm-nutzen strong{display:block;margin-bottom:4px;color:var(--ink)}
        .abm-nutzen p{margin:0;font-size:.94rem;color:var(--mut)}
        [data-theme="dark"] .abm-schritte li,[data-theme="dark"] .abm-need>div,
        [data-theme="dark"] .abm-nutzen>div{background:var(--soft)}
    </style>

    <section class="hero hero-sm reveal in">
        <h1>Kfz-Abmeldung – Fahrzeug online abmelden lassen</h1>
        <p class="lead">Die <strong>Außerbetriebsetzung</strong> ist der einfachste Behördenvorgang rund ums
            Auto – und trotzdem kostet er dich normalerweise Anfahrt, Wartemarke und einen halben Vormittag.
            Mit unserem Abmeldeservice meldest du dein Fahrzeug stattdessen <strong>komplett online</strong> ab.</p>
        <p style="margin:18px 0 0">
            <x-abmelde-cta variant="inline" class="cta" label="lp-hero" campaign="lp-hero">Fahrzeug jetzt online abmelden →</x-abmelde-cta>
        </p>
    </section>

    {{-- Service-Block mit USPs und Bewertung --}}
    <x-abmelde-cta label="lp-oben" campaign="lp"
                   titel="So läuft die Abmeldung über unseren Service"
                   text="Du gibst deine Fahrzeugdaten online ein, wir übernehmen die Außerbetriebsetzung – du bekommst die Abmeldebestätigung digital. Kein Termin, keine Warteschlange, kein Papierkram." />

    <section class="section reveal">
        <h2>In drei Schritten abgemeldet</h2>
        <ol class="abm-schritte">
            <li><strong>Fahrzeugdaten eingeben</strong>
                <span class="muted">Kennzeichen und die Daten aus der Zulassungsbescheinigung Teil I – der
                Bestellprozess führt dich Feld für Feld durch.</span></li>
            <li><strong>Auftrag absenden</strong>
                <span class="muted">Prüfen, bestätigen, fertig. Der Aufwand für dich liegt bei rund zwei Minuten.</span></li>
            <li><strong>Abmeldebestätigung erhalten</strong>
                <span class="muted">Du bekommst die Bestätigung der Außerbetriebsetzung digital – als Nachweis
                für Versicherung, Finanzamt oder Käufer.</span></li>
        </ol>
    </section>

    <section class="section reveal">
        <h2>Warum die Abmeldung über uns</h2>
        <p class="lead-intro">Die Außerbetriebsetzung ist schnell erledigt – wenn sie jemand für dich
            übernimmt. Wir kümmern uns um den Behördenteil, du gibst nur deine Fahrzeugdaten ein.</p>
        <div class="abm-nutzen">
            <div>
                <strong>Kein Behördengang</strong>
                <p>Kein Termin, keine Anfahrt, keine Wartemarke. Du erledigst die Abmeldung von zu Hause
                    aus – an jedem Tag und zu jeder Uhrzeit.</p>
            </div>
            <div>
                <strong>In 2 Minuten beauftragt</strong>
                <p>Fahrzeugdaten eingeben, absenden, fertig. Länger dauert der Teil, den du selbst machst,
                    nicht.</p>
            </div>
            <div>
                <strong>Digitale Abmeldebestätigung</strong>
                <p>Du bekommst den Nachweis der Außerbetriebsetzung digital – bereit für Versicherung,
                    Finanzamt oder Käufer.</p>
            </div>
            <div>
                <strong>Geld-zurück-Garantie</strong>
                <p>Sollte etwas nicht klappen, bekommst du dein Geld zurück. Bewertet mit
                    {{ $bew['wert'] ?? '4,8' }} von 5 Sternen bei {{ $bew['anzahl'] ?? '2.129' }}
                    {{ $bew['quelle'] ?? 'Google' }}-Rezensionen.</p>
            </div>
        </div>
    </section>

    <section class="section reveal">
        <h2>Was du für die Abmeldung brauchst</h2>
        {{-- Bewusst keine .card: die tragen einen Stretched-Link (.card a::after),
             der Fließtext-Links innerhalb der Karte unklickbar machen würde. --}}
        <div class="abm-need">
            <div>
                <strong>Zulassungsbescheinigung Teil I</strong>
                <p>Der Fahrzeugschein mit allen Fahrzeugdaten. Fehlt er, hilft der Ratgeber
                    <a href="{{ url('/kfz-ratgeber/fahrzeugschein-verloren') }}">Fahrzeugschein verloren</a>.</p>
            </div>
            <div>
                <strong>Kennzeichen</strong>
                <p>Beide Schilder mit Stempelplaketten. Was beim
                    <a href="{{ url('/kfz-ratgeber/kennzeichen-entstempeln') }}">Entstempeln</a> passiert,
                    erklären wir im Ratgeber.</p>
            </div>
            <div>
                <strong>Halterdaten</strong>
                <p>Name und Anschrift des eingetragenen Halters – so, wie sie im
                    Fahrzeugschein stehen.</p>
            </div>
        </div>
    </section>

    <section class="section reveal">
        <h2>Nach der Abmeldung: drei Dinge nicht vergessen</h2>
        <div class="box box-wichtig"><strong>Sonst läuft Geld weiter:</strong>
            Prüfe, ob deine Versicherung auf <strong>Ruheversicherung</strong> umgestellt wurde und ob die
            <strong>Kfz-Steuer</strong> eingestellt ist. Verkaufst du das Fahrzeug, halte die
            <strong>Abmeldebestätigung</strong> als Nachweis bereit.</div>
        <p>Willst du deine Kombination behalten, gib das bei der Abmeldung an – dann bleibt sie befristet für dich
            reserviert und lässt sich auf das nächste Auto übertragen. Wie das funktioniert, steht im Ratgeber
            <a href="{{ url('/kfz-ratgeber/wunschkennzeichen-mitnehmen') }}">Wunschkennzeichen mitnehmen</a>.
            Ein neues Wunschkennzeichen kannst du direkt hier prüfen:</p>
        <p><x-reservierung-cta label="kfz-abmeldung" campaign="lp-abmeldung" /></p>
    </section>

    <section class="section reveal faq" id="faq">
        <h2>Häufige Fragen zur Kfz-Abmeldung</h2>
        @foreach($faq as $f)
            <details class="faq-item">
                <summary>{{ $f[0] }}</summary>
                <div style="padding:14px 18px">{{ $f[1] }}</div>
            </details>
        @endforeach
    </section>

    @if($ratgeber->isNotEmpty())
    <section class="section reveal">
        <h2>Ratgeber rund um die Abmeldung</h2>
        <div class="grid">
            @foreach($ratgeber as $a)
                <div class="card reveal {{ 'reveal-d'.($loop->index % 3 + 1) }}">
                    <a href="{{ url('/kfz-ratgeber/'.$a->slug) }}"><strong>{{ $a->titel }}</strong></a>
                    @if($a->intro)<div class="muted">{{ \Illuminate\Support\Str::limit($a->intro, 110) }}</div>@endif
                </div>
            @endforeach
        </div>
    </section>
    @endif

    <x-abmelde-cta label="lp-unten" campaign="lp-unten" />

    <p class="muted" style="font-size:.85rem">Der Abmeldeservice ist ein kostenpflichtiges Angebot und keine
        amtliche Stelle. Die Außerbetriebsetzung selbst erfolgt nach § 14 FZV über die zuständige
        Zulassungsbehörde. Angaben ohne Gewähr, keine Rechtsberatung.</p>
</x-layout>
