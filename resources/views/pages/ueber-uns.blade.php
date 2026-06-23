<x-layout :title="$title" :description="$description" :canonical="$canonical">
    <nav class="breadcrumb"><a href="{{ url('/') }}">Start</a> › Über uns</nav>

    <h1>Über uns</h1>

    <p>{{ config('portal.site_name') }} ist ein <strong>nicht-amtliches Informationsangebot</strong>
    rund um Kfz-Zulassung und Wunschkennzeichen. Wir bündeln Behörden-Informationen,
    Kennzeichen-Wissen und Ratgeber-Inhalte an einer Stelle und leiten für die
    Reservierung an die zuständige Stelle bzw. einen externen Dienst weiter.</p>

    <h2>Woher unsere Daten stammen</h2>
    <ul>
        <li><strong>Zulassungsstellen</strong> (Adresse, Geo, Öffnungszeiten): OpenStreetMap
        (© OpenStreetMap-Mitwirkende, ODbL) — ohne Gewähr, maßgeblich sind die Angaben der Behörde.</li>
        <li><strong>Kennzeichen-Kürzel</strong>: Wikidata (gemeinfrei, CC0).</li>
        <li><strong>Ratgeber</strong>: redaktionell erstellt, mit Quellenangabe und Stand-Datum.</li>
    </ul>

    <h2>Wie wir arbeiten</h2>
    <p>Unsere Ratgeber nennen Quellen und Rechtsstand und stellen keine Rechtsberatung dar.
    Werbliche Inhalte und Partner-/Affiliate-Links sind als <em>Anzeige</em> gekennzeichnet.
    Datenstände werden regelmäßig aktualisiert; Hinweise auf Fehler nehmen wir gerne auf.</p>

    <p class="muted">Verantwortlich und Kontakt: siehe <a href="{{ url('/impressum') }}">Impressum</a>.
    Zum Umgang mit Daten: <a href="{{ url('/datenschutz') }}">Datenschutz</a>.</p>
</x-layout>
