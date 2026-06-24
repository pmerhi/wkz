# Ratgeber-Quelldateien

Markdown-Artikel mit YAML-Frontmatter (titel, slug, kategorie, tags, stand, quelle,
meta_title, meta_description, intro). Import in die Datenbank:

    php artisan import:ratgeber --path=$(pwd)/content/ratgeber

Hausstil: Du-Form, Ich-Perspektive, ≥800 Wörter, Callout-Boxen
(`<div class="box box-tipp|info|wichtig|check|kosten|frage">…</div>`),
interne Links nur auf existierende Seiten, 1–2 seriöse externe Quellen.
