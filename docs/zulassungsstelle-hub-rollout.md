# Rollout-Plan: Zulassungsstellen-Hub (eine Seite pro Stadt)

**Ziel:** Statt vieler dünner Einzelseiten pro Amt eine **kanonische Hub-Seite pro Stadt**
(Ort + Bundesland), die alle Zulassungsstellen dort auflistet (Stadt + Landkreis +
Nebenstellen). Behebt Keyword-Kannibalisierung und Thin Content; trifft die Suchintention
(„Zulassungsstelle {Stadt}"). Vorbild: KennzeichenKing.

Prototyp läuft unter `/zulassungsstelle-hub/{slug}` (noindex). Controller
`PageController::zulassungsstelleHub`, View `pages/zulassungsstelle-hub.blade.php`.

## Kennzahlen (Stand generierung)
- **813** Hub-Seiten (Städte), davon **198** mit mehreren Ämtern/Standorten.
- **266** nötige 301-Redirects → `docs/zulassungsstelle-hub-redirects.csv`.
- **25** Stellen **ohne `ort`** → können (noch) nicht gruppiert werden (siehe Voraussetzungen).

## Kanonische URL je Stadt (Regel)
Pro Gruppe (`ort` + `bundesland_id`) wird eine kanonische Stelle bestimmt:
1. Primär-Amt (`parent_id IS NULL`), dessen `slug == Str::slug(ort)` (der „saubere" Slug), sonst
2. Primär-Amt mit kürzestem Slug, sonst
3. Stelle mit kürzestem Slug.

Beispiel Kaiserslautern → kanonisch `/zulassungsstelle/kaiserslautern` (Stadt);
`/zulassungsstelle/kaiserslautern-land` → 301 darauf.

## Umsetzung (Reihenfolge)

### 1. Voraussetzungen (Daten)
- [ ] **25 Stellen ohne `ort`** prüfen/ergänzen (sonst fehlen sie im Hub). Separate Aufgabe.
- [x] „Kfz-"-Präfix wird im Titel entfernt (Anzeige via `Zulassungsstelle::anzeigeName()`).
      → in der Einzel-View ebenfalls anwenden, falls diese bestehen bleibt.

### 2. Routing (Kern der Umstellung)
`PageController::zulassungsstelle($slug)` erhält vorne eine Hub-Weiche:
```
Stelle/Ort zum Slug auflösen → kanonischen Slug der Stadt bestimmen
  wenn $slug !== kanonisch:  redirect(canonical, 301)   // deckt Kinder UND Zweit-Ämter ab, kein Chain
  sonst:                     Hub der Stadt rendern (indexierbar)
```
Das ersetzt die bisherige „Kind → Eltern"-Weiterleitung durch „irgendein Amt → Stadt-Kanonisch"
(ein Sprung, keine Ketten). Die bestehenden Fälle 3–5 (Bundesland-Listing, Gemeinde-Fallback)
bleiben erhalten.

### 3. Hub indexierbar schalten
- Hub-View: `robots` von `noindex,follow` auf `index,follow`.
- `<link rel="canonical">` = die kanonische Stadt-URL.
- Titel/Description je Stadt (bereits vorbereitet).
- JSON-LD: pro Standort ein `GovernmentOffice`/`LocalBusiness`-Item (ItemList) statt einer FAQ-Only-Seite.

### 4. Redirects
**Empfohlen (computed):** Die Redirects entstehen automatisch aus der Routing-Weiche (Schritt 2) –
keine 266 DB-Zeilen nötig, keine Pflege, keine Ketten.
**Alternativ/ergänzend (explizit):** `docs/zulassungsstelle-hub-redirects.csv` in die
`redirects`-Tabelle importieren (Admin „Redirects"), falls explizite/überschreibbare Regeln
gewünscht sind. Format: `from_path;to_path;stadt`.

### 5. Interne Verlinkung & Sitemap
- Sitemap nur noch kanonische Hub-URLs (Zweit-Ämter/Kinder raus).
- Interne Links (Ort-Seiten „zuständige Zulassungsstelle", Bundesland-Listing) auf kanonische URL zeigen lassen.
- Bundesland-Listing: je Stadt nur einen Eintrag.

### 6. Canary → Full
1. **Canary:** Weiche zunächst nur für 3–5 Städte aktiv (z. B. kaiserslautern, wuerzburg, magdeburg),
   Rendering + Redirects + Rich Results prüfen.
2. **Full:** Weiche für alle Städte aktiv, Sitemap neu, in der Search Console einreichen.

## Rollback
- Routing-Weiche ist ein Controller-Block → entfernen stellt sofort den Alt-Zustand her.
- Keine destruktiven Datenänderungen nötig (Anzeige/Anzeige-Name & Gruppierung sind rein lesend).
- Bei explizitem Redirect-Import: die importierten Zeilen wieder deaktivieren/löschen.

## Risiken
- **Redirect-Ketten** vermeiden → computed-Ansatz (Schritt 2) springt immer direkt auf kanonisch.
- **Gleichnamige Orte** (Friedberg BY/HE) → Gruppierung ist auf `bundesland_id` begrenzt, kein Merge.
- **Zwei Bezirke pro Stadt** (Stadt/Landkreis) landen bewusst auf einer Seite (wie KennzeichenKing) →
  Zuständigkeit über die Namen „(Stadt)"/„(Landkreis)" + optional Hinweis „für Einwohner der Stadt/des Landkreises".
