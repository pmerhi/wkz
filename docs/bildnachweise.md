# Ortsbilder & Bildnachweise (Städte-/Wahrzeichenfotos)

Städtebilder werden datenbankgestützt verwaltet (Tabelle `ortbilder`, Model
`App\Models\Ortbild`). Kandidaten werden automatisiert über **Openverse** recherchiert
(offene API von WordPress/Creative Commons; aggregiert u. a. Flickr + Wikimedia) – aktuell
für die **50 größten Städte** Deutschlands. Im **Filament-Admin** wählst du je Stadt ein
**Hero**- und **zwei Footer**-Bilder (Footer 1 + Footer 2, unten nebeneinander). Die
Attribution (TASL) liegt pro Bild in der DB und wird über `<x-bild-credit>` direkt am Bild
ausgegeben.

> **Warum Openverse statt Flickr-API:** Die Flickr-API-Nutzungsbedingungen verlangen
> für kommerzielle Anwendungen eine Freigabe (ggf. kostenpflichtig). Openverse hat
> diese Einschränkung nicht und liefert dieselben (auch Flickr-)Bilder samt geprüfter
> Lizenz + Attribution. Die **Foto-Lizenz** (CC BY/CC0/…) erlaubt die kommerzielle
> Nutzung des Bildes unabhängig davon, wie es gefunden wurde.

> Rechtlicher Rahmen: kommerzielles Portal → nur Bilder mit erlaubter kommerzieller
> Nutzung. `ortbilder:fetch` filtert automatisch auf CC BY, CC BY-SA, CC0 und
> Public Domain Mark. **CC BY-NC (nicht kommerziell) und CC BY-ND (keine Bearbeitung)
> sind ausgeschlossen.** CC BY-SA ist enthalten – für **unbearbeitete** Anzeige
> unproblematisch (Share-Alike greift nur bei Bearbeitungen; dann Zuschnitt vermeiden
> oder mit `--nur-frei` nur CC BY/CC0/PDM holen). Bei erkennbaren Personen, Logos oder
> moderner Architektur ggf. zusätzliche Rechte prüfen.

## Workflow

1. **Recherche** (50 größte Städte, je 6 Kandidaten) – kein API-Key nötig:
   ```
   php artisan ortbilder:fetch                 # alle Städte
   php artisan ortbilder:fetch --stadt=koeln   # nur eine Stadt
   php artisan ortbilder:fetch --nur-frei      # ohne Share-Alike (frei bearbeitbar)
   ```
   (Optionaler `OPENVERSE_API_TOKEN` in `.env` erhöht nur das Rate-Limit.)
2. **Auswahl** im Admin → „Inhalte → Ortsbilder": je Stadt **Hero** (Stern),
   **Footer 1** und **Footer 2** markieren. Unpassende → „Ablehnen".
   - **Auto-Download:** Bei der Auswahl wird das Bild sofort lokal nach
     `public/img/orte/{slug}-{hero|footer|footer2}.{ext}` geladen (web-taugliche Größe).
   - **Auto-Löschen:** Wird eine Auswahl aufgehoben (Ablehnen, andere Rolle, Löschen des
     Datensatzes oder Verdrängen durch ein neues Bild), wird die lokale Datei entfernt.
   - Diese Automatik steckt in `Ortbild::herunterladen()` / `lokaleDateiLoeschen()`
     (Events im Model) und `OrtbildResource::waehle()`.
3. **Nachladen/Reparieren** (optional, Bulk): Button „Auswahl herunterladen" im Admin
   oder `php artisan ortbilder:download` (mit `--force` alle neu). Ohne lokale Datei
   wird ersatzweise eine skalierte Direkt-URL der Quelle ausgeliefert.

Frontend: Hero-Bild oben auf der Ort-Seite (verschmolzener Hero, `og:image`/`twitter:image`),
zwei Footer-Bilder unten nebeneinander.

## Pflichtangaben je Lizenz (TASL)

| Lizenz | Kommerziell? | Namensnennung | Bearbeitung | Besonderheit |
|--------|:---:|:---:|:---:|---|
| **CC BY** (2.x–4.0) | ✅ | Pflicht | ✅ | Standardfall |
| **CC BY-SA** (2.x–4.0) | ✅ | Pflicht | ✅ (Share-Alike) | Unbearbeitet unproblematisch; Zuschnitt nur unter gleicher Lizenz. `--nur-frei` schließt sie aus |
| **CC0 1.0 / Public Domain Mark** | ✅ | freiwillig | ✅ | Nennung als Nachweis empfohlen |
| **CC BY-NC \* / CC BY-ND \*** | ❌ | – | – | **Ausgeschlossen** (nicht kommerziell / keine Bearbeitung) |

Hinweis: Die meisten Wikimedia-Wahrzeichenfotos sind **CC BY-SA** – für die
unbearbeitete Anzeige mit Namensnennung völlig ausreichend. Nur beim Zuschneiden/
Bearbeiten von BY-SA-Bildern greift die Share-Alike-Pflicht.

TASL = **T**itel · **A**utor · **S**ource (Quelle) · **L**izenz. Die
`<x-bild-credit>`-Komponente rendert daraus automatisch die `<figcaption>`;
Lizenz-Links kommen aus dem `lizenz_url`-Feld bzw. werden aus dem Kürzel abgeleitet.

## Komponente manuell verwenden (Sonderfälle)

```blade
<x-bild-credit
    src="img/orte/koeln-hero.jpg"
    alt="Blick über Köln mit Dom und Rheinufer"
    titel="Kölner Dom" autor="Max Mustermann"
    autor-url="https://www.flickr.com/photos/maxmustermann"
    quelle="https://www.flickr.com/photos/maxmustermann/123456789"
    lizenz="CC BY 2.0" :bearbeitet="true"
    variant="hero"          {{-- oder "footer" (kleiner/zentriert) --}}
    width="1200" height="675" loading="eager" />
```

## Beleg-Archivierung

Die Attribution steckt pro Bild in der DB. Für einen zusätzlichen manuellen Nachweis
(z. B. Screenshot der Flickr-Lizenzseite bei Recht­sunsicherheit) empfiehlt sich
`storage/bildnachweise/` – außerhalb des Web-Roots.
