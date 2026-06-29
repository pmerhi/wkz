<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>URL-Migration: wunschkennzeichen-reservieren.de → neues Portal</title>
<style>
  :root{--pri:#1d4ed8;--ink:#0f172a;--tx:#1e293b;--mut:#64748b;--line:#e2e8f0;--soft:#f8fafc;--ok:#16a34a;--ok-bg:#dcfce7;--warn:#d97706;--warn-bg:#fef3c7;--no:#dc2626;--no-bg:#fee2e2}
  *{box-sizing:border-box} body{margin:0;font:16px/1.6 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;color:var(--tx);background:#fff}
  .wrap{max-width:960px;margin:0 auto;padding:32px 20px 80px}
  h1{font-size:1.9rem;letter-spacing:-.02em;margin:0 0 .2em;color:var(--ink)}
  h2{font-size:1.35rem;margin:1.8em 0 .5em;color:var(--ink);border-top:1px solid var(--line);padding-top:1.2em}
  h3{font-size:1.05rem;margin:1.4em 0 .3em;color:var(--ink)}
  .lead{color:var(--mut);font-size:1.08rem;max-width:70ch}
  table{width:100%;border-collapse:collapse;margin:.6em 0 1em;font-size:.93rem}
  th,td{text-align:left;padding:9px 12px;border-bottom:1px solid var(--line);vertical-align:top}
  th{background:var(--soft);color:var(--mut);font-weight:600;font-size:.82rem;text-transform:uppercase;letter-spacing:.03em}
  code{background:var(--soft);border:1px solid var(--line);border-radius:5px;padding:1px 6px;font-size:.86em}
  .pill{display:inline-block;border-radius:20px;padding:2px 10px;font-size:.8rem;font-weight:700}
  .p-ok{background:var(--ok-bg);color:#166534}.p-warn{background:var(--warn-bg);color:#92400e}.p-no{background:var(--no-bg);color:#991b1b}
  .box{border:1px solid var(--line);border-radius:12px;padding:16px 18px;margin:1em 0;background:var(--soft)}
  .box-rec{background:#eff6ff;border-color:#bfdbfe}
  .box-warn{background:var(--warn-bg);border-color:#fcd34d}
  .num{font-weight:800;color:var(--ink)}
  .bar{height:8px;border-radius:4px;background:var(--line);overflow:hidden;margin-top:4px}
  .bar>span{display:block;height:100%;background:var(--ok)}
  ul{margin:.3em 0 1em;padding-left:1.2em}li{margin:.25em 0}
  .muted{color:var(--mut)} .small{font-size:.85rem}
  .step{display:flex;gap:12px;margin:.6em 0}.step b{flex:0 0 26px;height:26px;border-radius:50%;background:var(--pri);color:#fff;display:grid;place-items:center;font-size:.85rem}
</style>
</head>
<body>
<div class="wrap">

<h1>URL-Migration: <span style="color:var(--pri)">wunschkennzeichen-reservieren.de</span> → neues Portal</h1>
<p class="lead">Ziel: Die alte Seite durch das neue System ersetzen, <strong>ohne indexierte URLs zu verlieren</strong> –
möglichst <strong>ohne Weiterleitungen</strong> (alte Adressen liefern weiterhin direkt eine Seite, Status&nbsp;200).</p>

<div class="box small muted">Analyse-Stand: Sitemap der alten Seite mit <strong>2.596 URLs</strong> · abgeglichen mit der Datenbank des neuen Systems.</div>

<h2>1 · Alte URL-Struktur</h2>
<table>
  <thead><tr><th>Muster</th><th>Beispiel</th><th>Anzahl</th></tr></thead>
  <tbody>
    <tr><td><code>/wunschkennzeichen/{ort}/</code></td><td><code>/wunschkennzeichen/kiel/</code></td><td class="num">989</td></tr>
    <tr><td><code>/kennzeichen/{kürzel-id}/</code></td><td><code>/kennzeichen/abg-1354/</code></td><td class="num">783</td></tr>
    <tr><td><code>/zulassungsstelle/{ort}/</code></td><td><code>/zulassungsstelle/flensburg/</code></td><td class="num">748</td></tr>
    <tr><td><code>/kfz-zulassung/{thema}/</code></td><td><code>/kfz-zulassung/umweltplakette/</code></td><td class="num">23</td></tr>
    <tr><td><code>/kfz-kennzeichen/{thema}/</code></td><td><code>/kfz-kennzeichen/…/</code></td><td class="num">21</td></tr>
    <tr><td><code>/tipps-fuer-fahrzeughalter/{slug}/</code></td><td>Blog/Ratgeber</td><td class="num">13</td></tr>
    <tr><td><code>/kfz-ummeldung-abmeldung/{slug}/</code></td><td>Ratgeber</td><td class="num">8</td></tr>
    <tr><td><code>/{seite}/</code></td><td><code>/impressum/ /agb/ /faq/ /datenschutz/ /kfz-ratgeber/</code></td><td class="num">10</td></tr>
    <tr><td><code>/</code> + <code>/kennzeichen/</code></td><td>Startseite, Übersicht</td><td class="num">2</td></tr>
  </tbody>
</table>
<p class="small muted">Auffällig: Alle alten URLs enden mit <strong>Schrägstrich</strong> (<code>/</code>). Das neue System nutzt URLs <strong>ohne</strong> Schrägstrich.</p>

<h2>2 · Neue URL-Struktur (aktuell)</h2>
<table>
  <thead><tr><th>Zweck</th><th>Neue URL</th></tr></thead>
  <tbody>
    <tr><td>Kürzel-Seite</td><td><code>/kennzeichen/{kürzel}</code> (z.&nbsp;B. <code>/kennzeichen/abg</code>)</td></tr>
    <tr><td>Ort-/Wunschkennzeichen-Seite</td><td><code>/kennzeichen/ort/{ort}</code></td></tr>
    <tr><td>Zulassungsstelle (Detail)</td><td><code>/zulassungsstelle/{bundesland}/{ort}</code></td></tr>
    <tr><td>Bundesland-Übersicht</td><td><code>/zulassungsstelle/{bundesland}</code></td></tr>
    <tr><td>Ratgeber</td><td><code>/ratgeber/{thema}</code></td></tr>
    <tr><td>Statisch</td><td><code>/impressum</code> · <code>/datenschutz</code> · <code>/ueber-uns</code></td></tr>
  </tbody>
</table>

<h2>3 · Abgleich & Abdeckung</h2>
<p class="lead">Wie viele alte Seiten lassen sich im neuen System <strong>nativ</strong> (über vorhandene Daten) bedienen?</p>

@php
  $rows = [
    ['/kennzeichen/{kürzel-id}/', 783, 766, 'Kürzel-Code aus Slug ableiten (z. B. abg-1354 → Kürzel ABG)'],
    ['/zulassungsstelle/{ort}/', 748, 632, 'Ort-Slug = Slug der Zulassungsstelle'],
    ['/wunschkennzeichen/{ort}/', 989, 635, 'Ort/Stelle vorhanden → Ort-Kennzeichen-Seite'],
  ];
@endphp
<table>
  <thead><tr><th>Altes Muster</th><th>alt</th><th>nativ bedienbar</th><th>Abdeckung</th><th>Mechanik</th></tr></thead>
  <tbody>
  @foreach($rows as [$muster,$alt,$nativ,$mech])
    @php $q = round($nativ/$alt*100); @endphp
    <tr>
      <td><code>{{ $muster }}</code></td>
      <td class="num">{{ $alt }}</td>
      <td class="num">{{ $nativ }}</td>
      <td style="min-width:120px">
        <span class="pill {{ $q>=80?'p-ok':($q>=55?'p-warn':'p-no') }}">{{ $q }} %</span>
        <div class="bar"><span style="width:{{ $q }}%"></span></div>
      </td>
      <td class="small">{{ $mech }}</td>
    </tr>
  @endforeach
  </tbody>
</table>
<p class="small muted">Die „nicht nativ" abgedeckten Rest-Slugs (15–36 %) sind Orte/Stellen, die es im neuen Datenbestand (noch) nicht
gibt, oder leicht abweichende Slugs – siehe Schritt 5.</p>

<h2>4 · Vorschlag: „ohne Redirect" durch Alias-Routen</h2>
<p class="lead">Das neue System bekommt zusätzliche Routen, die die <strong>alten Adressen direkt bedienen</strong> (HTTP&nbsp;200) –
keine 301-Kette, kein Linkverlust. Pro Seite wird ein <code>canonical</code>-Tag auf die saubere neue URL gesetzt
(konsolidiert die Rankings, ist aber <em>keine</em> Weiterleitung).</p>

<table>
  <thead><tr><th>Alte URL</th><th>liefert (nativ)</th><th>canonical →</th></tr></thead>
  <tbody>
    <tr><td><code>/kennzeichen/abg-1354/</code></td><td>Kürzel-Seite ABG</td><td><code>/kennzeichen/abg</code></td></tr>
    <tr><td><code>/zulassungsstelle/flensburg/</code></td><td>Zulassungsstelle Flensburg</td><td><code>/zulassungsstelle/schleswig-holstein/flensburg</code></td></tr>
    <tr><td><code>/wunschkennzeichen/kiel/</code></td><td>Ort-Kennzeichen Kiel</td><td><code>/kennzeichen/ort/kiel</code></td></tr>
    <tr><td><code>/kfz-zulassung/umweltplakette/</code></td><td>Ratgeber Umweltplakette</td><td><code>/ratgeber/umweltplakette</code></td></tr>
    <tr><td><code>/kfz-ratgeber/</code>, <code>/faq/</code>, <code>/agb/</code></td><td>Ratgeber-/Info-Seite</td><td>jeweilige neue Seite</td></tr>
  </tbody>
</table>

<div class="box box-rec">
  <strong>Wichtig zur Konfliktauflösung:</strong> Eine einsegmentige URL <code>/zulassungsstelle/x</code> bedeutet im neuen
  System „Bundesland", in der alten Seite „Ort". Die Alias-Route prüft daher: Ist <code>x</code> ein Bundesland → Bundesland-Seite,
  sonst eine Zulassungsstelle → Stellen-Seite. Beide funktionieren parallel.
</div>
<div class="box box-warn">
  <strong>Schrägstrich:</strong> Das neue System muss die alten URLs <em>mit</em> abschließendem <code>/</code> akzeptieren
  (sonst greift sonst doch eine Weiterleitung). Das stellen wir global ein.
</div>

<h2>5 · Die Rest-Slugs (15–36 %) – drei Optionen</h2>
<p class="lead">Für alte Seiten, deren Ort/Stelle es im neuen Bestand nicht gibt:</p>
<div class="step"><b>A</b><div><strong>Daten nachziehen</strong> – fehlende Orte/Stellen importieren, dann greift die Alias-Route automatisch (echte Seite, kein Verlust). <span class="muted small">Beste SEO-Wirkung.</span></div></div>
<div class="step"><b>B</b><div><strong>301 auf die nächstbeste Seite</strong> (Kreis/Bundesland/Übersicht) – nur für die Rest-Slugs, nicht für die Masse. <span class="muted small">Kein 404, minimaler Linkverlust.</span></div></div>
<div class="step"><b>C</b><div><strong>Akzeptieren</strong>, dass einzelne sehr spezielle Alt-Seiten entfallen (404). <span class="muted small">Nur wenn ohne Relevanz.</span></div></div>

<h2>6 · Empfehlung & nächste Schritte</h2>
<div class="box box-rec">
  <ol style="margin:0;padding-left:1.1em">
    <li><strong>Alias-Routen</strong> für die 3 großen Muster + Ratgeber-Kategorien + statische Seiten (deckt ~2.300 URLs nativ ab).</li>
    <li><strong>Schrägstrich-Toleranz</strong> global aktivieren.</li>
    <li><strong>canonical-Tags</strong> auf die neuen sauberen URLs.</li>
    <li><strong>Rest-Slugs</strong>: Liste exportieren → Option A (Daten nachziehen) wo sinnvoll, sonst B (301).</li>
    <li>Vor Go-Live: alte Sitemap gegen das neue System testen (jede URL = 200/Canonical), dann DNS umstellen.</li>
  </ol>
</div>
<p class="small muted">Ergebnis: Die große Mehrheit der 2.596 Alt-URLs bleibt <strong>ohne Weiterleitung</strong> erreichbar; nur ein
kleiner Rest braucht Daten-Nachzug oder gezielte 301. So bleibt das aufgebaute Ranking erhalten.</p>

</div>
</body>
</html>
