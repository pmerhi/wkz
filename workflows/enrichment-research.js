export const meta = {
  name: 'enrichment-research',
  description: 'Recherchiert lokale Content-/Feature-Ideen zur Anreicherung der Ort- und Zulassungsstellen-Seiten',
  phases: [{ title: 'Recherche', detail: '6 Rechercheure mit verschiedenen Brillen durchsuchen das Web' }],
}

const IDEA = {
  type: 'object',
  properties: {
    ideas: {
      type: 'array',
      items: {
        type: 'object',
        properties: {
          titel: { type: 'string', description: 'kurzer, konkreter Titel der Idee' },
          kategorie: { type: 'string', enum: ['Daten', 'Lokal', 'Interaktiv', 'UGC', 'Wettbewerb', 'Wusstest', 'Sonstiges'] },
          beschreibung: { type: 'string' },
          umsetzung: { type: 'string', description: 'konkreter Umsetzungsvorschlag fürs Portal' },
          quelle: { type: 'string', description: 'Fund-/Beleg-URL' },
          wettbewerber: { type: 'string', description: 'welcher Wettbewerber macht das (falls)' },
          seo_wert: { type: 'integer', description: '1-5' },
          relevanz: { type: 'integer', description: 'Themenbezug Kfz/lokal 1-5' },
          aufwand: { type: 'integer', description: '1=klein..5=gross' },
        },
        required: ['titel', 'kategorie', 'beschreibung', 'umsetzung', 'seo_wert', 'relevanz', 'aufwand'],
      },
    },
  },
  required: ['ideas'],
}

const KONTEXT = `Kontext: Ein deutsches SEO-Portal rund um Kfz-Wunschkennzeichen, Kennzeichen-Kuerzel, Zulassungsstellen und Kfz-Ratgeber. Es gibt ~873 Zulassungsstellen-Seiten und ~10.000 Ort-Seiten ("Kennzeichen fuer [Ort]"). Ziel: diese lokalen Seiten einzigartiger machen, Thin Content vermeiden, Verweildauer erhoehen. Nutze WebSearch/WebFetch (per ToolSearch laden) fuer echte Recherche und belege Funde mit URLs. Bewerte jede Idee ehrlich: seo_wert, relevanz (Themenbezug!), aufwand je 1-5. Off-Topic-Ideen niedrig bei relevanz. Liefere 5-8 konkrete Ideen.`

const LENSES = [
  { key: 'wettbewerb', prompt: `${KONTEXT}\n\nBRILLE: Wettbewerbsanalyse. Untersuche kennzeichenking.de und 2-3 weitere deutsche Kfz-/Zulassungs-Portale. Welche lokalen Inhalte/Features ueber das reine Kennzeichen hinaus bieten sie auf Orts-/Zulassungsstellen-Seiten (Formulare, Wartezeiten, Bewertungen, Daten, Tools)? Leite uebernehmbare Ideen ab.` },
  { key: 'amtliche-daten', prompt: `${KONTEXT}\n\nBRILLE: Amtliche/offene Daten. Welche frei nutzbaren Datenquellen (KBA Kfz-Bestand je Zulassungsbezirk, Destatis/Regionalatlas, GENESIS, Statistische Landesaemter) lassen sich je Ort/Kreis faktisch und gequellt einbinden? Nenne konkrete Datensaetze + URLs.` },
  { key: 'zulassungsstelle-praxis', prompt: `${KONTEXT}\n\nBRILLE: Praktische Vor-Ort-Infos zur Zulassungsstelle. Welche nuetzlichen lokalen Praxis-Infos erhoehen Nutzwert und Verweildauer (Parken, OEPNV-Anbindung, typische Wartezeiten, was mitbringen, Barrierefreiheit, Online-Terminlage)? Wie quellen/pflegen?` },
  { key: 'interaktiv', prompt: `${KONTEXT}\n\nBRILLE: Interaktive On-Topic-Elemente. Welche Tools/Rechner/Quizze/Checks mit Kennzeichen-/Zulassungsbezug erhoehen Engagement (Verfuegbarkeitscheck, Kosten-Rechner, Kennzeichen-Quiz, Kuerzel-Raten)? Konkret + Aufwand.` },
  { key: 'ugc-engagement', prompt: `${KONTEXT}\n\nBRILLE: Nutzer-Inhalte (UGC). Welche nutzergenerierten Elemente erzeugen einzigartigen Content pro Seite (Bewertungen/Erfahrungen zur Zulassungsstelle, Wartezeit-Meldungen, Tipps, Umfragen)? Beachte Moderation/Recht.` },
  { key: 'lokal-breit', prompt: `${KONTEXT}\n\nBRILLE: Breiter Ortsbezug (auch nicht-Kennzeichen). Welche lokalen Inhalte ziehen Seiten mit Ortsbezug generell (Verkehr/ÖPNV, lokale Mobilitaet, regionale Identitaet/Geschichte, E-Mobilitaet/Ladesaeulen)? Bewerte relevanz ehrlich niedrig, wenn off-topic.` },
  { key: 'wusstest', prompt: `${KONTEXT}\n\nBRILLE: "Wusstest du das?" – interessante, ueberraschende oder lustige FAKTEN rund um Kfz-Kennzeichen und Fahrzeuge in Deutschland, die sich als kurze Fakten-Boxen auf den Seiten zeigen lassen. Beispiele fuer Themen: wieder eingefuehrte Altkennzeichen, Spezialkennzeichen (Y = Bundeswehr, X = NATO, 0 = Diplomaten/Behoerden), wie viele Unterscheidungszeichen/Ortskuerzel es gibt, woraus Nummernschilder bestehen (Aluminium, retroreflektierende Folie), wie viele Kfz in Deutschland zugelassen sind, kuerzeste/laengste Kuerzel, verbotene Buchstabenkombinationen (z.B. NS, SS, KZ), Geschichte der Kennzeichen, sowie FIRMEN-/FLOTTEN-KENNZEICHEN: Unternehmen/Organisationen, die erkennbar bestimmte Buchstaben- oder Nummern-Bloecke nutzen oder reservierte Wunschkennzeichen-Serien fahren (z.B. Deutsche Post/DHL, Autovermieter, Carsharing, Taxi-Unternehmen, grosse Werke wie VW in Wolfsburg). WICHTIG: nur belegbare Faelle, keine urbanen Legenden; bei unsicheren Behauptungen kennzeichne sie als unbestaetigt. Liefere PRO Idee EINEN konkreten, belegten Fakt: setze kategorie IMMER auf "Wusstest", schreibe den Fakt in beschreibung, einen knackigen Titel in titel, die Quelle in quelle. relevanz hoch (on-topic), aufwand niedrig. Belege jeden Fakt mit einer URL.` },
]

phase('Recherche')
const results = await parallel(LENSES.map((l) => () =>
  agent(l.prompt, { label: 'rech:' + l.key, schema: IDEA }).then((r) => ({ key: l.key, ideas: (r && r.ideas) || [] }))
))

const ideas = results.filter(Boolean).flatMap((r) => (r.ideas || []).map((i) => ({ ...i, _lens: r.key })))
log(`Ideen gesammelt: ${ideas.length} aus ${results.filter(Boolean).length} Brillen`)
return { count: ideas.length, ideas }