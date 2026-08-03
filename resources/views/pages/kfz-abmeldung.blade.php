@php
    $preis = config('abmeldung.preis');
    $bew   = config('abmeldung.bewertung');
@endphp
<x-layout :title="$title" :description="$description" :canonical="$canonical" :schemas="$schemas">
    <nav class="breadcrumb"><a href="{{ url('/') }}">Start</a> › Kfz-Abmeldung</nav>

    <style>
        .abm-wege{width:100%;border-collapse:collapse;margin:0 0 8px}
        .abm-wege th,.abm-wege td{border:1px solid var(--line);padding:11px 14px;text-align:left;vertical-align:top}
        .abm-wege th{background:var(--soft);color:var(--ink);font-size:.92rem}
        .abm-wege td:first-child{font-weight:700;color:var(--ink)}
        .abm-wege .ja{color:var(--ok);font-weight:700}
        .abm-wege .nein{color:var(--mut)}
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
        [data-theme="dark"] .abm-schritte li,[data-theme="dark"] .abm-need>div{background:var(--soft)}
        [data-theme="dark"] .abm-wege th{background:var(--soft2)}
        @media(max-width:640px){
            .abm-wege{display:block;overflow-x:auto;white-space:nowrap}
        }
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
        @if($preis)
            <p class="muted" style="margin-top:14px">Preis: <strong>{{ $preis }}</strong> inkl. Amtsgebühren.</p>
        @else
            <p class="muted" style="margin-top:14px">Den Preis siehst du transparent im Bestellprozess,
                bevor du etwas verbindlich beauftragst.</p>
        @endif
    </section>

    {{-- Ehrlicher Vergleich der drei Wege – schafft Vertrauen und deckt die Suchintention ab --}}
    <section class="section reveal">
        <h2>Abmeldeservice, i-Kfz oder selbst zur Zulassungsstelle?</h2>
        <p class="lead-intro">Es gibt drei Wege, ein Fahrzeug abzumelden. Welcher der richtige ist, hängt
            vor allem davon ab, ob du einen freigeschalteten Online-Ausweis hast und wie viel Zeit du investieren willst.</p>
        <table class="abm-wege">
            <thead>
                <tr>
                    <th></th>
                    <th>Abmeldeservice</th>
                    <th>i-Kfz selbst</th>
                    <th>Zulassungsstelle</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>Behördengang</td><td class="ja">nicht nötig</td><td class="ja">nicht nötig</td><td class="nein">persönlich vor Ort</td></tr>
                <tr><td>Online-Ausweis (eID) nötig</td><td class="ja">nein</td><td class="nein">ja, mit PIN</td><td class="ja">nein</td></tr>
                <tr><td>Aufwand für dich</td><td>ca. 2 Minuten</td><td>ca. 15 Minuten Einrichtung</td><td>Anfahrt + Wartezeit</td></tr>
                <tr><td>Kosten</td><td>{{ $preis ?: 'Servicegebühr, im Bestellprozess ausgewiesen' }}</td><td>nur Amtsgebühr</td><td>Amtsgebühr ca. 5–11 €</td></tr>
                <tr><td>Abmeldebestätigung</td><td>digital</td><td>digital</td><td>vor Ort</td></tr>
            </tbody>
        </table>
        <p class="muted" style="font-size:.85rem">Gebühren sind Richtwerte nach GebOSt und variieren je Zulassungsstelle. Angaben ohne Gewähr.</p>
        <div class="box box-info"><strong>Du willst es selbst erledigen?</strong>
            Völlig in Ordnung – die Schritt-für-Schritt-Anleitung für beide Wege steht in unserem Ratgeber
            <a href="{{ url('/kfz-ratgeber/auto-abmelden') }}">Auto abmelden</a>, die Voraussetzungen für das
            Online-Verfahren unter <a href="{{ url('/kfz-ratgeber/i-kfz-online-zulassung') }}">i-Kfz</a>.
            Deine zuständige Behörde findest du im <a href="{{ url('/zulassungsstelle') }}">Zulassungsstellen-Verzeichnis</a>.</div>
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
        Zulassungsbehörde. Angaben zu Gebühren und Fristen sind Richtwerte und keine Rechtsberatung.</p>
</x-layout>
