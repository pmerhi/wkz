<?php

namespace App\Console\Commands;

use App\Models\Wettbewerber;
use App\Models\Zulassungsstelle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Stellt unsere Zulassungsstellen den (sauberen) kennzeichenking-Stellen gegenüber
 * und macht Dubletten auf unserer Seite sichtbar: alle Einträge werden über einen
 * „Büro-Schlüssel" (PLZ + Straße/Ort) gruppiert. Wo mehrere unserer Stellen denselben
 * Schlüssel teilen, sind es Dubletten. Ausgabe als CSV (Semikolon, UTF-8/BOM für Excel).
 */
class ReportDuplikate extends Command
{
    protected $signature = 'report:duplikate {--out= : Zielpfad der CSV} {--html= : Zielpfad der HTML-Ansicht}';

    protected $description = 'Gegenüberstellung unsere Stellen ↔ kennzeichenking (CSV + interaktive HTML), markiert Dubletten.';

    public function handle(): int
    {
        $out = $this->option('out') ?: storage_path('app/duplikate-vs-kennzeichenking.csv');

        $w = Wettbewerber::where('domain', 'kennzeichenking.de')->first();
        if (! $w) { $this->error('Wettbewerber kennzeichenking.de fehlt.'); return self::FAILURE; }

        // kennzeichenking als Referenz: nach Straße, PLZ und Ort indexieren.
        $kkByStreet = []; $kkByPlz = []; $kkByOrt = [];
        foreach (DB::table('extrakt_zulassungsstelle')->where('wettbewerber_id', $w->id)->get() as $r) {
            $kkByStreet[$this->officeKey($r->plz, $r->strasse, $r->ort)] ??= $r;
            $kkByPlz[trim((string) $r->plz)][] = $r;
            $kkByOrt[$this->norm($r->ort)][] = $r;
        }

        // Jede unserer Stellen einer kennzeichenking-Stelle zuordnen (Kaskade).
        $gruppen = [];   // gruppe_id => ['kk' => row|null, 'level' => str, 'stellen' => []]
        foreach (Zulassungsstelle::with('bundesland')->orderBy('ort')->get() as $s) {
            [$kk, $level] = $this->matchKk($s, $kkByStreet, $kkByPlz, $kkByOrt);
            $plz = trim((string) $s->plz); $ortKey = $this->norm($s->ort);
            if ($kk) {
                $gid = 'KK|'.$this->officeKey($kk->plz, $kk->strasse, $kk->ort);
            } elseif ($plz !== '' || $ortKey !== '') {
                $gid = 'OURS|'.$plz.'|'.$ortKey;   // interne Dubletten ohne KK-Treffer
            } else {
                $gid = 'SOLO|'.$s->id;             // ohne Ort/PLZ: nicht gruppierbar, keine Dublette
            }
            $gruppen[$gid]['kk'] = $kk;
            $gruppen[$gid]['level'] = $level;
            $gruppen[$gid]['stellen'][] = $s;
        }

        // Zeilen bauen.
        $rows = [];
        foreach ($gruppen as $gid => $g) {
            $kk = $g['kk']; $anzahl = count($g['stellen']);
            foreach ($g['stellen'] as $s) {
                $rows[] = [
                    'ist_dublette'      => $anzahl > 1 ? 'JA' : '',
                    'anzahl_unsere'     => $anzahl,
                    'match'             => $kk ? $g['level'] : 'kein KK-Treffer',
                    'kk_name'           => $kk->name ?? '',
                    'kk_strasse'        => $kk->strasse ?? '',
                    'kk_plz'            => $kk->plz ?? '',
                    'kk_ort'            => $kk->ort ?? '',
                    'unser_id'          => $s->id,
                    'unser_name'        => $s->name,
                    'unser_strasse'     => $s->strasse,
                    'unser_plz'         => $s->plz,
                    'unser_ort'         => $s->ort,
                    'unser_bundesland'  => $s->bundesland?->name,
                    'unser_slug'        => $s->land_slug.'/'.$s->slug,
                    'unser_quelle'      => $s->quelle,
                    'gruppe_key'        => $gid,
                ];
            }
        }

        // Sortierung: Dubletten zuerst, dann nach Gruppe (Gruppen bleiben zusammen).
        usort($rows, fn ($a, $b) =>
            [$b['anzahl_unsere'] > 1 ? 1 : 0, $a['gruppe_key']]
            <=> [$a['anzahl_unsere'] > 1 ? 1 : 0, $b['gruppe_key']]);

        // CSV schreiben (UTF-8 BOM + Semikolon für deutsches Excel).
        $fh = fopen($out, 'w');
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, array_keys($rows[0] ?? ['leer' => '']), ';');
        foreach ($rows as $r) fputcsv($fh, $r, ';');
        fclose($fh);

        // Kennzahlen.
        $dubGruppen = collect($gruppen)->filter(fn ($g) => count($g['stellen']) > 1);
        $dubStellen = $dubGruppen->sum(fn ($g) => count($g['stellen']));
        $ueberhang  = $dubGruppen->sum(fn ($g) => count($g['stellen']) - 1);
        $ohneKk     = collect($gruppen)->filter(fn ($g) => ! $g['kk'])->sum(fn ($g) => count($g['stellen']));

        $this->info('CSV geschrieben: '.$out);
        $this->line('Gruppen gesamt: '.count($gruppen)
            .' · Dubletten-Gruppen (≥2 unserer Stellen): '.$dubGruppen->count()
            .' · betroffene Stellen: '.$dubStellen
            .' · rechnerischer Überhang: '.$ueberhang);
        $this->line('Unsere Stellen ohne kennzeichenking-Treffer: '.$ohneKk);

        // Interaktive HTML-Ansicht (optional).
        $html = $this->option('html');
        if ($html !== null) {
            $htmlPath = $html ?: storage_path('app/duplikate-vs-kennzeichenking.html');
            $this->writeHtml($gruppen, $htmlPath, [
                'stellen'    => Zulassungsstelle::count(),
                'gruppen'    => count($gruppen),
                'dubGruppen' => $dubGruppen->count(),
                'dubStellen' => $dubStellen,
                'ueberhang'  => $ueberhang,
                'ohneKk'     => $ohneKk,
            ]);
            $this->info('HTML geschrieben: '.$htmlPath);
        }
        return self::SUCCESS;
    }

    /** Schreibt eine eigenständige, interaktive HTML-Ansicht (Suche, Filter, URL-Vergleich). */
    private function writeHtml(array $gruppen, string $path, array $stats): void
    {
        $base = rtrim((string) config('app.url'), '/');
        $data = [];
        foreach ($gruppen as $gid => $g) {
            $kk = $g['kk'];
            $anzahl = count($g['stellen']);
            $kkKey = $kk ? $this->streetKey((string) $kk->strasse) : null;
            $ours = [];
            foreach ($g['stellen'] as $s) {
                $pfad = '/zulassungsstelle/'.$s->land_slug.'/'.$s->slug;
                $ours[] = [
                    'id'         => $s->id,
                    'name'       => (string) $s->name,
                    'strasse'    => (string) $s->strasse,
                    'plz'        => (string) $s->plz,
                    'ort'        => (string) $s->ort,
                    'bundesland' => (string) ($s->bundesland?->name ?? ''),
                    'quelle'     => (string) $s->quelle,
                    'pfad'       => $pfad,
                    'url'        => $base.$pfad,
                    'istKk'      => $kk && $s->strasse && $this->streetKey((string) $s->strasse) === $kkKey,
                ];
            }
            // matchende (= kennzeichenking-Adresse) zuerst zeigen
            usort($ours, fn ($a, $b) => ($b['istKk'] ? 1 : 0) <=> ($a['istKk'] ? 1 : 0));
            $data[] = [
                'ort'    => $g['stellen'][0]->ort ?: ($kk->ort ?? ''),
                'land'   => $g['stellen'][0]->bundesland?->name ?? '',
                'anzahl' => $anzahl,
                'isDup'  => $anzahl > 1,
                'match'  => $kk ? $g['level'] : 'kein KK-Treffer',
                'kk'     => $kk ? [
                    'name'    => (string) $kk->name,
                    'strasse' => (string) $kk->strasse,
                    'plz'     => (string) $kk->plz,
                    'ort'     => (string) $kk->ort,
                    'url'     => (string) ($kk->quelle_url ?? ''),
                ] : null,
                'ours'   => $ours,
            ];
        }
        // Dubletten zuerst, dann nach Ort.
        usort($data, fn ($a, $b) => [$b['isDup'] ? 1 : 0, $a['ort']] <=> [$a['isDup'] ? 1 : 0, $b['ort']]);

        $json = json_encode(['stats' => $stats, 'groups' => $data],
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
        file_put_contents($path, str_replace('__DATA__', $json, $this->htmlTemplate()));
    }

    private function htmlTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Doppelte Zulassungsstellen — Gegenüberstellung mit kennzeichenking</title>
<style>
  :root{--line:#e3e6ea;--mut:#667085;--bg:#f6f7f9;--card:#fff;--kk:#0b62d6;--ok:#0a7c3a;--dup:#b54708;--dupbg:#fff7ed;--okbg:#ecfdf3;}
  *{box-sizing:border-box}
  body{margin:0;font:15px/1.55 -apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1a2230;background:var(--bg)}
  header{position:sticky;top:0;z-index:10;background:#fff;border-bottom:1px solid var(--line);padding:14px 20px}
  h1{font-size:1.25rem;margin:0 0 4px}
  .stats{color:var(--mut);font-size:.9rem}
  .stats b{color:#1a2230}
  .toolbar{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;align-items:center}
  .toolbar input[type=search]{flex:1;min-width:220px;padding:8px 10px;border:1px solid var(--line);border-radius:8px;font-size:.95rem}
  .chip{border:1px solid var(--line);background:#fff;border-radius:20px;padding:5px 12px;cursor:pointer;font-size:.85rem;color:#344}
  .chip.active{background:#1a2230;color:#fff;border-color:#1a2230}
  label.toggle{display:flex;align-items:center;gap:6px;font-size:.88rem;color:#344;cursor:pointer}
  main{padding:18px 20px;max-width:1100px;margin:0 auto}
  .group{background:var(--card);border:1px solid var(--line);border-radius:12px;margin:0 0 16px;overflow:hidden}
  .ghead{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:12px 16px;border-bottom:1px solid var(--line);background:#fbfcfe}
  .gtitle{font-weight:600;font-size:1.05rem}
  .gtitle .land{color:var(--mut);font-weight:400;font-size:.85rem;margin-left:6px}
  .badge{font-size:.74rem;padding:3px 9px;border-radius:20px;white-space:nowrap}
  .b-dup{background:var(--dupbg);color:var(--dup);border:1px solid #fdba74}
  .b-uni{background:#eef2f7;color:#475467}
  .b-match{background:#eef4ff;color:var(--kk);border:1px solid #bcd2ff;margin-left:6px}
  .kk{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;padding:12px 16px;background:#f4f8ff;border-bottom:1px dashed #cfe0ff}
  .kk .lab{font-size:.72rem;letter-spacing:.04em;text-transform:uppercase;color:var(--kk);font-weight:700}
  .kk .addr{margin-top:2px}
  a.btn{font-size:.82rem;text-decoration:none;color:#fff;background:var(--kk);padding:6px 11px;border-radius:7px;white-space:nowrap}
  a.btn.alt{background:#1a2230}
  table{width:100%;border-collapse:collapse}
  td,th{padding:9px 16px;text-align:left;vertical-align:top;border-top:1px solid var(--line);font-size:.9rem}
  th{font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;color:var(--mut);background:#fcfcfd}
  tr.is-kk{background:var(--okbg)}
  tr.is-kk td:first-child{box-shadow:inset 3px 0 0 var(--ok)}
  tr.is-dup td:first-child{box-shadow:inset 3px 0 0 var(--dup)}
  .pill{font-size:.7rem;padding:2px 7px;border-radius:12px}
  .pill.ok{background:var(--okbg);color:var(--ok)}
  .pill.dup{background:var(--dupbg);color:var(--dup)}
  .src{color:var(--mut);font-size:.8rem}
  .urls a{display:block;font-size:.82rem;color:var(--kk);text-decoration:none;word-break:break-all}
  .urls a:hover{text-decoration:underline}
  .muted{color:var(--mut)}
  .empty{padding:40px;text-align:center;color:var(--mut)}
  footer{padding:20px;text-align:center;color:var(--mut);font-size:.8rem}
</style>
</head>
<body>
<header>
  <h1>Doppelte Zulassungsstellen — Abgleich mit kennzeichenking</h1>
  <div class="stats" id="stats"></div>
  <div class="toolbar">
    <input type="search" id="q" placeholder="Suchen: Ort, Name, PLZ, Straße …">
    <span class="chip active" data-f="alle">Alle</span>
    <span class="chip" data-f="PLZ+Straße">PLZ+Straße</span>
    <span class="chip" data-f="PLZ">PLZ</span>
    <span class="chip" data-f="Ort">Ort</span>
    <span class="chip" data-f="kein KK-Treffer">kein Treffer</span>
    <label class="toggle"><input type="checkbox" id="onlyDup" checked> nur Dubletten</label>
  </div>
</header>
<main id="list"></main>
<footer>Grün = Adresse stimmt mit kennzeichenking überein · Orange = mögliche Dublette. Vergleiche die URLs und Anschriften, um zu entscheiden, welche bleiben soll.</footer>
<script id="data" type="application/json">__DATA__</script>
<script>
const DB = JSON.parse(document.getElementById('data').textContent);
const esc = s => (s||'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
document.getElementById('stats').innerHTML =
  `<b>\${DB.stats.stellen}</b> Stellen · <b>\${DB.stats.dubGruppen}</b> Dubletten-Gruppen · `+
  `<b>\${DB.stats.dubStellen}</b> betroffene Stellen · rechnerischer Überhang <b>\${DB.stats.ueberhang}</b> · `+
  `ohne kennzeichenking-Treffer <b>\${DB.stats.ohneKk}</b>`;

let filter='alle', onlyDup=true, q='';
function matchRow(g){
  if(onlyDup && !g.isDup) return false;
  if(filter!=='alle' && g.match!==filter) return false;
  if(q){ const h=(g.ort+' '+g.land+' '+(g.kk?g.kk.name+' '+g.kk.strasse:'')+' '+g.ours.map(o=>o.name+' '+o.strasse+' '+o.plz+' '+o.ort).join(' ')).toLowerCase();
         if(!h.includes(q)) return false; }
  return true;
}
function render(){
  const list=document.getElementById('list');
  const items=DB.groups.filter(matchRow);
  if(!items.length){ list.innerHTML='<div class="empty">Keine Treffer für diese Filter.</div>'; return; }
  list.innerHTML=items.slice(0,800).map(g=>{
    const badge = g.isDup?`<span class="badge b-dup">\${g.anzahl} Einträge — Dublette</span>`:`<span class="badge b-uni">\${g.anzahl} Eintrag</span>`;
    const mb = g.kk?`<span class="badge b-match">Treffer: \${esc(g.match)}</span>`:`<span class="badge b-match">kein kennzeichenking-Treffer</span>`;
    const kk = g.kk?`<div class="kk"><div><div class="lab">kennzeichenking</div>
        <div><b>\${esc(g.kk.name)}</b></div>
        <div class="addr">\${esc(g.kk.strasse)} · \${esc(g.kk.plz)} \${esc(g.kk.ort)}</div></div>
        \${g.kk.url?`<a class="btn" href="\${esc(g.kk.url)}" target="_blank" rel="noopener">kennzeichenking ↗</a>`:''}</div>`:'';
    const rows = g.ours.map(o=>`<tr class="\${o.istKk?'is-kk':(g.isDup?'is-dup':'')}">
        <td>\${o.istKk?'<span class="pill ok">= kennzeichenking</span>':(g.isDup?'<span class="pill dup">prüfen</span>':'')}</td>
        <td><b>\${esc(o.name)}</b><div class="src">#\${o.id} · \${esc(o.quelle).slice(0,46)}</div></td>
        <td>\${esc(o.strasse)}<div class="muted">\${esc(o.plz)} \${esc(o.ort)}</div></td>
        <td class="urls">
          <a href="\${esc(o.url)}" target="_blank" rel="noopener">unsere Seite ↗</a>
          <span class="muted">\${esc(o.pfad)}</span>
        </td></tr>`).join('');
    return `<section class="group"><div class="ghead"><div class="gtitle">\${esc(g.ort)}<span class="land">\${esc(g.land)}</span></div><div>\${badge}\${mb}</div></div>
       \${kk}
       <table><thead><tr><th></th><th>Unser Eintrag</th><th>Anschrift</th><th>URL-Vergleich</th></tr></thead><tbody>\${rows}</tbody></table>
     </section>`;
  }).join('') + (items.length>800?`<div class="empty">… \${items.length-800} weitere Gruppen ausgeblendet. Nutze Suche/Filter.</div>`:'');
}
document.getElementById('q').addEventListener('input',e=>{q=e.target.value.trim().toLowerCase();render();});
document.getElementById('onlyDup').addEventListener('change',e=>{onlyDup=e.target.checked;render();});
document.querySelectorAll('.chip').forEach(c=>c.addEventListener('click',()=>{
  document.querySelectorAll('.chip').forEach(x=>x.classList.remove('active'));
  c.classList.add('active'); filter=c.dataset.f; render();
}));
render();
</script>
</body>
</html>
HTML;
    }

    /**
     * Ordnet eine Stelle der besten kennzeichenking-Stelle zu:
     * 1) PLZ+Straße exakt  2) eindeutige PLZ  3) eindeutiger Ort.
     * @return array{0: ?object, 1: string}
     */
    private function matchKk(Zulassungsstelle $s, array $byStreet, array $byPlz, array $byOrt): array
    {
        if ($s->strasse && isset($byStreet[$this->officeKey($s->plz, $s->strasse, $s->ort)])) {
            return [$byStreet[$this->officeKey($s->plz, $s->strasse, $s->ort)], 'PLZ+Straße'];
        }
        $plz = trim((string) $s->plz);
        if ($plz !== '' && count($byPlz[$plz] ?? []) === 1) {
            return [$byPlz[$plz][0], 'PLZ'];
        }
        $ort = $this->norm($s->ort);
        if ($ort !== '' && count($byOrt[$ort] ?? []) === 1) {
            return [$byOrt[$ort][0], 'Ort'];
        }
        return [null, ''];
    }

    /** Büro-Schlüssel: PLZ + Straßenname(+1. Hausnr.), sonst PLZ + Ort. */
    private function officeKey(?string $plz, ?string $strasse, ?string $ort): string
    {
        $plz = trim((string) $plz);
        if ($strasse) {
            return $plz.'|'.$this->streetKey($strasse);
        }
        return $plz.'|'.$this->norm($ort).'|ns';
    }

    private function streetKey(string $s): string
    {
        $s = Str::lower($s);
        $s = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $s);
        $s = preg_replace('/stra(ss|ß)e|str\.?/u', 'str', $s);
        $num = preg_match('/\d+/', $s, $m) ? $m[0] : '';
        $name = preg_replace('/[^a-z0-9]+/u', '', preg_replace('/\d.*$/u', '', $s));
        return $name.$num;
    }

    private function norm(?string $s): string
    {
        $s = Str::lower((string) $s);
        $s = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $s);
        return preg_replace('/[^a-z0-9]+/u', '', $s);
    }
}
