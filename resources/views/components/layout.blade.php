@props([
    'title' => null,
    'description' => '',
    'canonical' => null,
    'robots' => 'index,follow',
    'ogType' => 'website',
    'schemas' => [],
    // Kopfzeile: leer = Portal-Logo (Standard). Gesetzt (z.B. auf Zulassungsstellen-
    // Seiten) = fixer Amtsname statt Logo, z.B. „Straßenverkehrsamt München".
    // Ist 'brand' gesetzt, wird der Titel NICHT verlinkt (Besucher bleibt auf der Seite).
    'brand' => null,
    // Kopf-Menü: leer = globale Seitennavigation (Standard). Gefüllt = nur seiten-
    // interne Sprungmarken [['href'=>'#…','label'=>'…'], …], um Besucher zu halten.
    'navLinks' => [],
])
@php $ogImage = config('portal.og_image'); @endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>(function(){try{var d=document.documentElement;var t=localStorage.getItem('theme');var dark=t==='dark'||((!t||t==='auto')&&matchMedia('(prefers-color-scheme:dark)').matches);if(dark)d.setAttribute('data-theme','dark');var f=parseInt(localStorage.getItem('fontpx'),10);if(f>=14&&f<=22)d.style.fontSize=f+'px';}catch(e){}})();</script>
    <title>{{ $title ?? config('portal.site_name') }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="{{ $robots }}">
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
    <meta name="theme-color" content="#055cc5">

    {{-- Open Graph --}}
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:locale" content="de_DE">
    <meta property="og:site_name" content="{{ config('portal.site_name') }}">
    <meta property="og:title" content="{{ $title ?? config('portal.site_name') }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    @if($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ $ogImage }}">
    @else
    <meta name="twitter:card" content="summary">
    @endif
    <meta name="twitter:title" content="{{ $title ?? config('portal.site_name') }}">
    <meta name="twitter:description" content="{{ $description }}">

    {{-- Strukturierte Daten (Schema.org) --}}
    @foreach ($schemas as $schema)
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endforeach

    <style>
        /* Amtliche Kennzeichen-Schrift (Datei unter public/fonts/ ablegen) */
        @font-face{font-family:"gl-nummernschild";font-display:swap;
            src:url("/fonts/gl-nummernschild-mtl.woff2") format("woff2"),
                url("/fonts/gl-nummernschild-mtl.woff") format("woff"),
                url("/fonts/gl-nummernschild-mtl.ttf") format("truetype");}
        /* Titillium Web – Seitenschrift (selbst gehostet, Anlehnung an Referenz-Design) */
        @font-face{font-family:"Titillium Web";font-weight:400;font-style:normal;font-display:swap;
            src:url("/fonts/titillium-web/titillium-web-regular.woff2") format("woff2");}
        @font-face{font-family:"Titillium Web";font-weight:600;font-style:normal;font-display:swap;
            src:url("/fonts/titillium-web/titillium-web-600.woff2") format("woff2");}
        @font-face{font-family:"Titillium Web";font-weight:700;font-style:normal;font-display:swap;
            src:url("/fonts/titillium-web/titillium-web-700.woff2") format("woff2");}
        :root{
            --pri:#055cc5; --pri-d:#004bb1; --pri-l:#3d82d6;
            --ok:#29aa5d; --ok-bg:#e3f5ea; --warn:#d97706; --warn-bg:#fef3c7; --no:#dc2626; --no-bg:#fee2e2;
            --ink:#000000; --tx:#1f1f1f; --mut:#888888; --bg:#ffffff; --page:#f1f1f1; --soft:#f1f1f1; --soft2:#e8e8e8; --line:#d9d9d9;
            --shadow:0 1px 5px 0 rgba(0,0,0,.25);
            --shadow-lg:0 8px 30px -8px rgba(0,0,0,.28);
            --r:4px; --maxw:1080px;
        }
        /* Dark Mode (umschaltbar oben rechts) */
        [data-theme="dark"]{
            --ink:#f1f5f9; --tx:#cbd5e1; --mut:#94a3b8;
            --bg:#0f172a; --page:#0b1120; --soft:#1e293b; --soft2:#162033; --line:#334155; --pri-l:#60a5fa;
            --shadow:0 1px 2px rgba(0,0,0,.3),0 6px 20px -6px rgba(0,0,0,.5);
            --shadow-lg:0 10px 40px -12px rgba(0,0,0,.7);
        }
        [data-theme="dark"] header.site{background:rgba(15,23,42,.85)}
        [data-theme="dark"] a,[data-theme="dark"] .content a,[data-theme="dark"] nav.breadcrumb a:hover{color:#60a5fa}
        [data-theme="dark"] .card,[data-theme="dark"] table.info,[data-theme="dark"] .oz,
        [data-theme="dark"] .faq details,[data-theme="dark"] .faq-item,[data-theme="dark"] .ac-panel,
        [data-theme="dark"] .quiz-opt,[data-theme="dark"] .hs-table,[data-theme="dark"] .quiz-info,
        [data-theme="dark"] .hero-search input,[data-theme="dark"] .gen-controls input,
        [data-theme="dark"] .quiz-name,[data-theme="dark"] .badge,[data-theme="dark"] .ac-item{
            background:var(--soft);color:var(--tx);border-color:var(--line)}
        [data-theme="dark"] table.info th,[data-theme="dark"] .hs-table th,
        [data-theme="dark"] .ac-item.active,[data-theme="dark"] .ac-item:hover{background:var(--soft2)}
        [data-theme="dark"] .box{background:var(--soft2);border-color:var(--line);color:var(--tx)}
        [data-theme="dark"] .wusstest-box{background:linear-gradient(135deg,#3a2f0a,#4a3a0c);border-color:#a16207}
        [data-theme="dark"] .wusstest-titel,[data-theme="dark"] .wusstest-text{color:#fde68a}
        @media(max-width:760px){[data-theme="dark"] nav.main{background:var(--soft)}}
        *{box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{margin:0;font:1rem/1.65 "Titillium Web",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;color:var(--tx);background:var(--page);-webkit-font-smoothing:antialiased;transition:background .2s,color .2s}
        .wrap{max-width:var(--maxw);margin:0 auto;padding:0 20px}
        .wrap--narrow{max-width:760px}
        a{color:var(--pri)}
        img{max-width:100%;height:auto}

        /* Header */
        header.site{position:sticky;top:0;z-index:50;background:rgba(255,255,255,.82);backdrop-filter:saturate(160%) blur(10px);border-bottom:1px solid var(--line)}
        header.site .wrap{display:flex;align-items:center;justify-content:space-between;gap:16px;height:100px;transition:height .25s ease}
        /* Beim Scrollen schrumpft der Header (Klasse .is-small per JS) */
        header.site.is-small .wrap{height:64px}
        header.site.is-small .brand-logo{height:46px}
        .brand{display:flex;align-items:center;gap:9px;font-weight:800;letter-spacing:-.02em;font-size:1.12rem;text-decoration:none;color:var(--ink)}
        .brand--static{cursor:default}
        .brand .brand-logo{height:80px;width:auto;display:block;transition:height .25s ease}
        .brand .brand-amt{margin:0;font-weight:800;letter-spacing:-.02em;font-size:clamp(1rem,2.4vw,1.2rem);line-height:1.15;color:var(--ink)}
        /* Zertifikats-Badges im Header (SSL, DIN) – wie Referenz, auf Mobil ausgeblendet */
        .header-badges{display:flex;align-items:center;gap:12px;flex:0 0 auto}
        .header-badges img{height:48px;width:auto;display:block;transition:height .25s ease}
        header.site.is-small .header-badges img{height:34px}
        @media(max-width:900px){.header-badges{display:none}}
        /* Logo im Dark Mode aufhellen (SVG-Schrift ist nahezu schwarz) */
        [data-theme="dark"] .brand .brand-logo{filter:invert(1) hue-rotate(180deg) brightness(1.05)}
        nav.main{display:flex;align-items:center;gap:4px}
        /* Seiten-internes Kopf-Menü (z.B. Zulassungsstelle): viele Sprungmarken
           dürfen die Kopfzeile nicht sprengen → bei Überlauf horizontal scrollen. */
        nav.main.nav--inpage{flex-wrap:nowrap;overflow-x:auto;max-width:100%;gap:1px;scrollbar-width:thin;scrollbar-color:var(--line) transparent}
        /* Deutlich kompakter als die globale Nav: kleinere Schrift, kleinere Emoji-Icons, enger */
        nav.main.nav--inpage a{white-space:nowrap;flex:0 0 auto;font-size:.72rem;font-weight:600;padding:4px 7px;letter-spacing:-.005em}
        nav.main.nav--inpage::-webkit-scrollbar{height:6px}
        nav.main.nav--inpage::-webkit-scrollbar-thumb{background:var(--line);border-radius:3px}
        nav.main a{text-decoration:none;color:var(--tx);font-weight:600;font-size:.95rem;padding:8px 12px;border-radius:9px;transition:background .15s,color .15s}
        nav.main a:hover{background:var(--soft);color:var(--ink)}
        .nav-toggle{display:none;background:none;border:1px solid var(--line);border-radius:9px;width:42px;height:38px;font-size:1.2rem;cursor:pointer;color:var(--tx)}
        .header-right{display:flex;align-items:center;gap:10px}
        .header-tools{display:flex;gap:5px;align-items:center}
        .tool-btn{min-width:34px;height:34px;padding:0 8px;border:1px solid var(--line);background:var(--bg);color:var(--tx);border-radius:8px;cursor:pointer;font-weight:700;font-size:.9rem;line-height:1;display:inline-flex;align-items:center;justify-content:center;transition:.15s}
        .tool-btn:hover{background:var(--soft);border-color:var(--pri-l);color:var(--ink)}
        /* Darstellungs-Modal */
        .modal-overlay{position:fixed;inset:0;z-index:200}
        .modal-overlay[hidden]{display:none}
        .modal{position:fixed;top:60px;right:12px;background:var(--bg);color:var(--tx);border:1px solid var(--line);border-radius:14px;box-shadow:var(--shadow-lg);width:300px;max-width:calc(100vw - 24px);overflow:hidden}
        .modal-head{display:flex;align-items:center;justify-content:space-between;padding:15px 20px;border-bottom:1px solid var(--line)}
        .modal-head h2{margin:0;font-size:1.15rem}
        .modal-close{background:none;border:none;font-size:1.7rem;line-height:1;cursor:pointer;color:var(--mut);width:38px;height:38px;border-radius:9px}
        .modal-close:hover{background:var(--soft);color:var(--ink)}
        .modal-body{padding:18px 20px;display:grid;gap:20px}
        .set-group{display:grid;gap:9px}
        .set-label{font-weight:700;font-size:.9rem;color:var(--ink)}
        .seg{display:flex;gap:6px;flex-wrap:wrap}
        .seg-btn{flex:1 1 0;min-width:88px;padding:10px;border:1px solid var(--line);background:var(--bg);color:var(--tx);border-radius:10px;cursor:pointer;font-weight:600;font-size:.9rem;transition:.15s}
        .seg-btn:hover{background:var(--soft)}
        .seg-btn.active{background:var(--pri);color:#fff;border-color:var(--pri)}
        .set-font{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
        .js-font-val{font-weight:700;min-width:56px;text-align:center;color:var(--ink)}
        @media(max-width:760px){
            header.site .wrap{height:60px}
            .brand .brand-logo{height:40px}
            header.site.is-small .wrap{height:52px}
            header.site.is-small .brand-logo{height:34px}
            nav.main{position:fixed;inset:60px 0 auto 0;flex-direction:column;align-items:stretch;background:#fff;border-bottom:1px solid var(--line);padding:8px 16px 16px;box-shadow:var(--shadow);transform:translateY(-130%);transition:transform .25s ease;gap:2px}
            nav.main.open{transform:translateY(0)}
            nav.main a{padding:12px 10px}
            .nav-toggle{display:block}
        }

        main{padding:34px 0 8px;min-height:50vh}
        h1{font-size:clamp(1.8rem,4vw,2.5rem);line-height:1.12;letter-spacing:-.025em;margin:.1em 0 .45em;color:var(--ink);font-weight:800}
        h2{font-size:clamp(1.3rem,2.4vw,1.6rem);line-height:1.2;letter-spacing:-.02em;margin:1.8em 0 .5em;color:var(--ink);font-weight:750}
        h3{font-size:1.12rem;margin:1.4em 0 .35em;color:var(--ink);font-weight:700}
        p{margin:0 0 1em}
        .lead{font-size:1.16rem;color:var(--mut);max-width:62ch}
        .muted{color:var(--mut)}

        /* Hero */
        .hero{position:relative;overflow:hidden;border-radius:24px;margin:6px 0 26px;padding:clamp(28px,5vw,56px);color:#fff;
            background:linear-gradient(135deg,#004bb1,#055cc5 55%,#3d82d6);box-shadow:var(--shadow-lg)}
        .hero::before,.hero::after{content:"";position:absolute;border-radius:50%;filter:blur(2px);opacity:.5;pointer-events:none}
        .hero::before{width:340px;height:340px;right:-90px;top:-120px;background:radial-gradient(circle,rgba(255,255,255,.30),transparent 70%);animation:float 14s ease-in-out infinite}
        .hero::after{width:260px;height:260px;left:-80px;bottom:-120px;background:radial-gradient(circle,rgba(125,211,252,.35),transparent 70%);animation:float 18s ease-in-out infinite reverse}
        .hero h1{color:#fff;margin-top:0;max-width:16ch}
        .hero .lead{color:rgba(255,255,255,.92)}
        .hero .hero-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:22px}
        .hero .trust{display:flex;flex-wrap:wrap;gap:18px;margin-top:24px;font-size:.92rem;color:rgba(255,255,255,.92)}
        .hero .trust span{display:inline-flex;align-items:center;gap:7px}
        @keyframes float{0%,100%{transform:translate(0,0)}50%{transform:translate(-14px,18px)}}
        .hero.hero-sm{padding:clamp(22px,3.6vw,38px);border-radius:20px;margin:0 0 24px}
        .hero.hero-sm h1{font-size:clamp(1.6rem,3.4vw,2.15rem)}
        .hero-search{display:flex;gap:8px;max-width:460px;margin-top:18px}
        .hero-search input{flex:1;min-width:0;padding:13px 16px;border:none;border-radius:11px;font-size:1rem;box-shadow:0 8px 24px -10px rgba(0,0,0,.4)}
        mark{background:#fde68a;color:inherit;padding:0 .12em;border-radius:3px}
        /* Autocomplete-Vorschläge – per JS an <body> gehängt und fixed positioniert,
           damit overflow:hidden des Hero-Bereichs das Panel nicht abschneidet. */
        .ac-panel{position:fixed;background:#fff;border:1px solid var(--line);border-radius:12px;
            box-shadow:0 18px 40px -14px rgba(0,0,0,.35);overflow-y:auto;max-height:min(70vh,420px);
            z-index:120;display:none;text-align:left}
        .ac-panel.open{display:block}
        .ac-item{display:block;padding:10px 14px;text-decoration:none;color:var(--tx);border-bottom:1px solid var(--soft);cursor:pointer}
        .ac-item:last-child{border-bottom:none}
        .ac-item strong{font-weight:600;font-size:.96rem;color:var(--ink)}
        .ac-item small{display:block;color:var(--mut);font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;margin-top:2px}
        .ac-item.active,.ac-item:hover{background:var(--soft2)}
        .ac-item mark{background:#fde68a}
        .ac-all{display:block;padding:9px 14px;font-size:.85rem;font-weight:600;color:var(--pri);text-decoration:none;background:var(--soft2);border-top:1px solid var(--line)}
        .ac-all.active{background:#e8f1fb}

        /* Buttons */
        .cta,.btn{display:inline-flex;align-items:center;gap:8px;background:var(--pri);color:#fff;padding:13px 24px;border-radius:11px;text-decoration:none;font-weight:700;border:none;cursor:pointer;font-size:1rem;
            box-shadow:0 6px 16px -6px rgba(5,92,197,.6);transition:transform .15s,box-shadow .15s,background .15s}
        .cta:hover,.btn:hover{transform:translateY(-2px);box-shadow:0 12px 24px -8px rgba(5,92,197,.7);background:var(--pri-d)}
        .hero .cta{background:#fff;color:var(--pri-d);box-shadow:0 10px 30px -10px rgba(0,0,0,.45)}
        .hero .cta:hover{background:#f8fafc}
        .btn-ghost{background:transparent;color:#fff;box-shadow:none;border:1.5px solid rgba(255,255,255,.6)}
        .btn-ghost:hover{background:rgba(255,255,255,.12);box-shadow:none}

        /* Cards / grid */
        .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:14px}
        .card{position:relative;background:#fff;border:1px solid var(--line);border-radius:var(--r);padding:16px;box-shadow:var(--shadow);transition:transform .18s,box-shadow .18s,border-color .18s}
        .card:hover{transform:translateY(-3px);box-shadow:var(--shadow-lg);border-color:#cdd7e5}
        .card a{text-decoration:none;color:var(--ink);font-weight:700}
        .card a::after{content:"";position:absolute;inset:0}
        .card .muted{font-size:.92rem}
        /* Download-Karten: Inhalt gestapelt, Button unten bündig über alle Karten */
        .card-dl{display:flex;flex-direction:column;gap:6px}
        .card-dl .card-desc{font-size:.92rem;color:var(--mut)}
        .btn-dl{margin-top:auto;display:inline-flex;align-items:center;justify-content:center;gap:8px;
            width:100%;box-sizing:border-box;padding:11px 14px;border-radius:10px;font-weight:700;font-size:.92rem;text-decoration:none;
            color:var(--pri-d);background:var(--soft2);border:1px solid var(--line);transition:background .15s,color .15s,border-color .15s}
        .card-dl:hover .btn-dl{background:var(--pri);color:#fff;border-color:var(--pri)}

        /* Badges / chips */
        .badge{display:inline-block;border:1px solid var(--line);border-radius:8px;padding:3px 10px;margin:2px;font-size:.9rem;text-decoration:none;color:var(--tx);background:#fff;transition:.15s}
        a.badge:hover{border-color:var(--pri-l);color:var(--pri);background:var(--soft2);transform:translateY(-1px)}
        .badge-alt{border-color:#055cc5;background:#e8f1fb;color:#055cc5;font-weight:700}

        /* Altkennzeichen-Infografik (iframe der interaktiven Karte) */
        .ak-infografik{margin:28px 0}
        .ak-infografik-cap{margin:0 0 14px;padding:0}
        .ak-infografik-titel{margin:0 0 4px}
        .ak-infografik-intro{margin:0;max-width:62ch}
        .ak-infografik-frame{width:462px;max-width:100%;margin:0 auto}
        .ak-infografik-frame iframe{width:462px;max-width:100%;height:840px;border:1px solid var(--line);border-radius:12px;background:#fff;display:block}

        /* Interaktive Deutschlandkarte (Altkennzeichen nach Bundesland) */
        .de-karte-wrap{margin:28px 0}
        .de-karte-titel{margin:0 0 4px}
        .de-karte-intro{margin:0 0 14px}
        .de-karte-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(260px,360px);gap:24px;align-items:start}
        .de-karte-svg-box{min-width:0}
        .de-karte-svg{width:100%;height:auto;display:block}
        .de-karte-svg path{stroke:var(--bg,#fff);stroke-width:1.1;stroke-linejoin:round;transition:fill .15s,filter .15s}
        .de-karte-svg path.is-hover{filter:brightness(1.06)}
        .de-karte-svg path.is-aktiv{stroke:var(--pri);stroke-width:2.2;filter:drop-shadow(0 1px 3px rgba(0,0,0,.25))}
        .de-karte-svg path:focus{outline:none}
        .de-karte-svg path:focus-visible{stroke:var(--pri);stroke-width:2.4}
        .de-karte-legende{display:flex;align-items:center;gap:8px;margin-top:10px;font-size:.8rem}
        .de-karte-leg-bar{flex:0 0 90px;height:10px;border-radius:5px;background:linear-gradient(90deg,hsl(199 92% 92%),hsl(199 92% 40%))}
        .de-karte-panel{border:1px solid var(--line);border-radius:14px;padding:16px 18px;background:var(--card,#fff);position:sticky;top:90px}
        .de-karte-panel-titel{margin:0 0 4px}
        .de-karte-panel-meta{margin:0 0 12px;font-size:.9rem}
        .de-karte-codes{display:flex;flex-wrap:wrap;gap:2px;margin-bottom:14px}
        .de-karte-panel-link{font-weight:600;text-decoration:none;color:var(--pri)}
        .de-karte-panel-link:hover{text-decoration:underline}
        @media(max-width:740px){.de-karte-grid{grid-template-columns:1fr}.de-karte-panel{position:static}}
        [data-theme="dark"] .de-karte-svg path{stroke:var(--card,#1a1d22)}

        nav.breadcrumb{font-size:.88rem;margin-bottom:12px;color:var(--mut)}
        nav.breadcrumb a{color:var(--mut);text-decoration:none}
        nav.breadcrumb a:hover{color:var(--pri)}

        .detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;margin:.5em 0}
        @media(max-width:760px){.detail-grid{grid-template-columns:1fr}}
        .col h2{margin-top:.2em}
        table.info{border-collapse:collapse;width:100%;background:#fff;border:1px solid var(--line);border-radius:var(--r);overflow:hidden;box-shadow:var(--shadow)}
        table.info th,table.info td{text-align:left;padding:11px 16px;border-bottom:1px solid var(--line);vertical-align:top}
        table.info tr:last-child th,table.info tr:last-child td{border-bottom:none}
        table.info th{width:180px;color:var(--mut);font-weight:600;background:var(--soft2)}
        .stellen-liste{list-style:none;padding:0;margin:.4em 0;columns:2;column-gap:28px}
        @media(max-width:560px){.stellen-liste{columns:1}}
        .stellen-liste li{margin:.18em 0;break-inside:avoid}
        .stellen-liste a{text-decoration:none;font-weight:600;color:var(--ink)}
        .stellen-liste a:hover{color:var(--pri)}

        /* Öffnungszeiten-Widget */
        .oz{background:#fff;border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow);overflow:hidden;margin:.5em 0 1em}
        .oz-head{display:flex;align-items:center;gap:10px;padding:14px 18px;font-size:1.05rem;border-bottom:1px solid var(--line)}
        .oz-head .oz-dot{width:11px;height:11px;border-radius:50%;flex:0 0 auto;box-shadow:0 0 0 0 currentColor}
        .oz-offen{background:var(--ok-bg);color:#166534}
        .oz-bald{background:var(--warn-bg);color:#92400e}
        .oz-zu{background:var(--no-bg);color:#991b1b}
        .oz-unbekannt{background:var(--soft);color:var(--mut)}
        .oz-offen .oz-dot,.oz-bald .oz-dot{background:currentColor;animation:pulse 2s infinite}
        .oz-zu .oz-dot{background:currentColor}
        @keyframes pulse{0%{box-shadow:0 0 0 0 rgba(22,163,74,.45)}70%{box-shadow:0 0 0 8px rgba(22,163,74,0)}100%{box-shadow:0 0 0 0 rgba(22,163,74,0)}}
        .oz-chart{padding:30px 18px 14px;position:relative}
        .oz-axis{position:relative;height:0;margin:0 64px 6px 46px}
        .oz-tick{position:absolute;top:-22px;transform:translateX(-50%);font-size:.72rem;color:var(--mut);white-space:nowrap}
        .oz-row{display:grid;grid-template-columns:42px 1fr 116px;align-items:center;gap:8px;padding:3px 0}
        .oz-row .oz-day{font-weight:700;font-size:.9rem;color:var(--tx);display:flex;flex-direction:column;line-height:1.1}
        .oz-row .oz-day small{font-weight:600;color:var(--pri);font-size:.66rem}
        .oz-track{position:relative;height:18px;background:repeating-linear-gradient(90deg,var(--soft) 0 1px,transparent 1px 25%);border-radius:6px;background-color:var(--soft2)}
        .oz-bar{position:absolute;top:2px;bottom:2px;background:linear-gradient(180deg,var(--pri-l),var(--pri));border-radius:5px;box-shadow:0 1px 3px rgba(5,92,197,.4)}
        .oz-row.is-today .oz-bar{background:linear-gradient(180deg,#22c55e,#29aa5d);box-shadow:0 1px 3px rgba(22,163,74,.45)}
        .oz-row.is-today{background:linear-gradient(90deg,rgba(34,197,94,.08),transparent);border-radius:8px}
        .oz-zu{font-size:.74rem;color:var(--mut);padding-left:6px;line-height:18px;background:none}
        .oz-now{position:absolute;top:-3px;bottom:-3px;width:2px;background:#ef4444;border-radius:2px}
        .oz-now::before{content:"";position:absolute;top:-3px;left:-3px;width:8px;height:8px;border-radius:50%;background:#ef4444}
        .oz-times{font-size:.8rem;color:var(--mut);text-align:right;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .oz-hint{font-size:.78rem;padding:0 18px 12px;margin:0}
        /* „Heute"-Vorschau */
        .oz-today{padding:18px 18px 6px}
        .oz-today-top{display:flex;justify-content:space-between;align-items:baseline;gap:12px;margin-bottom:9px}
        .oz-today-tag{font-weight:750;color:var(--ink)}
        .oz-today-times{font-weight:650;color:var(--tx)}
        .oz-track-lg{height:30px;border-radius:8px}
        .oz-track-lg .oz-bar{border-radius:7px}
        .oz-axis-lg{position:relative;height:16px;margin-top:4px}
        .oz-axis-lg span{position:absolute;transform:translateX(-50%);font-size:.7rem;color:var(--mut)}
        details.oz-week{border-top:1px solid var(--line);margin-top:8px}
        details.oz-week>summary{cursor:pointer;padding:12px 18px;font-weight:650;color:var(--pri);list-style:none;display:flex;align-items:center;gap:8px;user-select:none}
        details.oz-week>summary::-webkit-details-marker{display:none}
        details.oz-week>summary::before{content:"▸";transition:transform .2s}
        details.oz-week[open]>summary::before{transform:rotate(90deg)}
        details.oz-week>summary:hover{background:var(--soft2)}
        .oz-closed-lbl{font-size:.74rem;color:var(--mut);padding-left:6px;line-height:18px}
        .oz-table{width:100%;border-collapse:collapse;margin:0}
        .oz-table th,.oz-table td{text-align:left;padding:10px 18px;border:none;font-size:.95rem}
        .oz-table th{width:130px;font-weight:600;color:var(--tx)}
        .oz-table th small{font-weight:600;color:var(--pri);font-size:.7rem;margin-left:4px}
        .oz-table tr.is-today{background:rgba(34,197,94,.08)}
        .oz-table tr.is-today th{color:#166534}
        .oz-table tr.is-closed td{color:var(--mut)}
        @media(max-width:560px){
            .oz-row{grid-template-columns:34px 1fr;gap:6px}
            .oz-times{display:none}
            .oz-axis{margin-right:8px}
        }

        /* Luftige Sektionen + Feature-Block (kennzeichenking-Stil) */
        .section{padding:26px 0;border-top:1px solid var(--line)}
        .section:first-of-type{border-top:none}
        .section>h2{margin-top:0}
        .lead-intro{font-size:1.12rem;color:var(--mut);max-width:68ch}
        .feature{position:relative;overflow:hidden;background:linear-gradient(135deg,#e8f1fb,#dbe9f8);border:1px solid #bcd6f2;border-radius:20px;padding:clamp(22px,4vw,34px);box-shadow:var(--shadow)}
        .feature .tag-new{display:inline-block;background:var(--pri);color:#fff;font-size:.72rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase;padding:5px 11px;border-radius:30px;margin-bottom:12px}
        .feature h2{margin:0 0 .35em}
        .feature .grid{margin-top:18px}
        .feature .card{background:rgba(255,255,255,.7)}
        .jumpnav{display:flex;flex-wrap:wrap;gap:8px;margin:6px 0 8px}
        .jumpnav a{font-size:.9rem;text-decoration:none;color:var(--tx);background:var(--soft);border:1px solid var(--line);padding:7px 13px;border-radius:30px;transition:.15s}
        .jumpnav a:hover{background:#fff;border-color:var(--pri-l);color:var(--pri)}
        .faq details{background:#fff;border:1px solid var(--line);border-radius:12px;margin:0 0 10px;box-shadow:var(--shadow)}
        .faq summary{cursor:pointer;padding:15px 18px;font-weight:700;color:var(--ink);list-style:none;position:relative}
        .faq summary::-webkit-details-marker{display:none}
        .faq summary::after{content:"+";position:absolute;right:18px;font-weight:400;font-size:1.3rem;color:var(--pri);transition:transform .2s}
        .faq details[open] summary::after{content:"–"}
        .faq details>p{padding:0 18px 16px;margin:0;color:var(--tx)}
        /* Übernommene FAQ-Antworten (HTML in .faq-answer): großzügiges Padding + Abstände */
        .faq details .faq-answer{padding:6px 20px 20px;color:var(--tx)}
        .faq details .faq-answer>:first-child{margin-top:0}
        .faq details .faq-answer>:last-child{margin-bottom:0}
        .faq details .faq-answer p{margin:0 0 .8em}
        .faq details .faq-answer ul,.faq details .faq-answer ol{margin:.3em 0 .9em;padding-left:1.4em}
        .faq details .faq-answer li{margin:.25em 0}
        /* Aufklappbare Checkliste (Was mitbringen) */
        .faq-item{background:#fff;border:1px solid var(--line);border-radius:12px;margin:0 0 10px;box-shadow:var(--shadow);overflow:hidden}
        .faq-item>summary{cursor:pointer;padding:14px 18px;font-weight:700;color:var(--ink);list-style:none;position:relative}
        .faq-item>summary::-webkit-details-marker{display:none}
        .faq-item>summary::after{content:"+";position:absolute;right:18px;font-weight:400;font-size:1.3rem;color:var(--pri)}
        .faq-item[open]>summary{border-bottom:1px solid var(--line)}
        .faq-item[open]>summary::after{content:"–"}
        .check-list{list-style:none;margin:0;padding:12px 18px}
        .check-list li{padding:5px 0 5px 28px;position:relative;color:var(--tx)}
        .check-list li::before{content:"✓";position:absolute;left:4px;color:var(--pri);font-weight:800}
        /* Wunschkennzeichen-Generator (Plate-Vorschau) */
        .kfz-plate{display:inline-flex;align-items:stretch;height:62px;border:2px solid #111;border-radius:7px;background:#fff;overflow:hidden;box-shadow:var(--shadow);margin:4px 0}
        .kfz-eu{background:#039;color:#fff;width:30px;display:flex;flex-direction:column;align-items:center;justify-content:space-between;padding:6px 0;font-size:.62rem}
        .kfz-stars{color:#fc0;line-height:1;font-size:.8rem}
        .kfz-d{font-weight:700}
        .kfz-body{display:flex;align-items:center;padding:0 14px;font:800 1.9rem/1 "Arial Narrow",Arial,sans-serif;letter-spacing:1px;color:#111;white-space:nowrap}
        .gen-controls{display:flex;gap:14px;margin-top:14px;flex-wrap:wrap}
        .gen-controls label{font-size:.82rem;color:var(--mut);font-weight:600}
        .gen-controls input{display:block;margin-top:4px;padding:10px 12px;border:1px solid var(--line);border-radius:9px;font-size:1.1rem;font-weight:700;text-transform:uppercase;width:120px;letter-spacing:1px}
        a.js-gen-kombi{cursor:pointer}
        .gen-status{margin:12px 0 0;font-size:.9rem;min-height:1.2em;font-weight:600}
        .gen-status.ok{color:#29aa5d}
        .gen-status.err{color:#dc2626}
        .cta.is-disabled{opacity:.5;pointer-events:none;filter:grayscale(.3)}
        /* Kennzeichen-Eingabemaske (direkt im Schild tippen) */
        .nt-row{display:flex;gap:18px;align-items:center;flex-wrap:wrap}
        .nt-plate-wrap{flex:0 1 480px;max-width:100%}
        .nt-plate{display:grid;grid-template-columns:46px 1.1fr 20px 1fr 1.5fr;align-items:stretch;height:clamp(70px,13vw,94px);border:1px solid #111;border-radius:6px;background:#fff;overflow:hidden;box-shadow:0 4px 14px -5px rgba(0,0,0,.4);font-family:"gl-nummernschild","Arial Narrow",Arial,sans-serif}
        .nt-plate-eu{background:#078ac5;color:#fff;display:flex;flex-direction:column;align-items:center;justify-content:space-between;padding:9px 0 6px}
        .nt-plate-stars{width:62%;color:#fc0;display:block}
        .nt-plate-d{font-weight:700;font-size:clamp(.8rem,2.2vw,1.05rem);line-height:1}
        .nt-in{border:none;background:transparent;text-align:center;text-transform:uppercase;color:#111;font-family:"gl-nummernschild","Arial Narrow",Arial,sans-serif;font-weight:800;font-size:clamp(1.6rem,6vw,2.9rem);letter-spacing:1px;outline:none;min-width:0;height:100%;padding:0}
        .nt-in-letters,.nt-in-numbers{border-left:1px solid #e5e7eb}
        .nt-in-numbers{box-shadow:inset 0 0 0 2px #fcd34d}
        .nt-in:focus{background:#fff7ed;box-shadow:inset 0 0 0 2px #f59e0b}
        .nt-seal{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:7px}
        .nt-seal .seal-small{width:8px;height:8px;border-radius:50%;background:#9aa6b2}
        .nt-seal .seal-big{width:13px;height:13px;border-radius:50%;background:#7c8896}
        .nt-plate-labels{display:grid;grid-template-columns:46px 1.1fr 20px 1fr 1.5fr;margin-top:6px}
        .nt-plate-labels label{text-align:center;font-size:.76rem;color:var(--mut)}
        /* Andrang-Wochenverlauf (Beste Besuchszeit) */
        .andrang{display:flex;align-items:flex-end;gap:16px;height:118px;max-width:360px;margin:6px 0 4px}
        .andrang-col{display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:6px}
        .andrang-bar{width:36px;border-radius:6px 6px 0 0;background:#22c55e}
        .andrang-bar.lvl-3{background:#f59e0b}
        .andrang-bar.lvl-4{background:#ef4444}
        .andrang-col b{font-size:.82rem;color:var(--mut)}
        /* Kennzeichen-Quiz */
        .quiz-head{display:flex;gap:16px;flex-wrap:wrap;color:var(--mut);font-weight:600;margin-bottom:14px}
        .quiz-opts{display:grid;gap:10px;margin-top:16px;max-width:540px}
        .quiz-opt{text-align:left;padding:13px 16px;border:1px solid var(--line);border-radius:11px;background:#fff;font-weight:600;cursor:pointer;font-size:1rem;transition:.12s}
        .quiz-opt:hover:not(:disabled){border-color:var(--pri-l);background:var(--soft2)}
        .quiz-opt.correct{background:#dcfce7;border-color:#22c55e}
        .quiz-opt.wrong{background:#fee2e2;border-color:#ef4444}
        .quiz-stats{display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:10px;font-weight:700;max-width:540px}
        .quiz-lives{font-size:1.25rem;letter-spacing:3px}
        .quiz-timer{height:10px;background:var(--soft);border-radius:6px;overflow:hidden;margin-bottom:16px;max-width:540px}
        .quiz-timer>span{display:block;height:100%;background:var(--pri);width:100%}
        .quiz-timer.warn>span{background:#ef4444}
        .quiz-name{padding:13px 16px;border:1px solid var(--line);border-radius:11px;font-size:1.05rem;width:260px;max-width:100%}
        .hs-tabs{display:flex;gap:6px;flex-wrap:wrap;margin:8px 0 14px}
        .hs-tab{padding:8px 14px;border:1px solid var(--line);border-radius:9px;background:#fff;cursor:pointer;font-weight:600;font-size:.9rem;color:var(--tx)}
        .hs-tab.active{background:var(--pri);color:#fff;border-color:var(--pri)}
        .hs-table{width:100%;max-width:540px;border-collapse:collapse;background:#fff;border:1px solid var(--line);border-radius:12px;overflow:hidden}
        .hs-table th,.hs-table td{padding:9px 14px;text-align:left;border-bottom:1px solid var(--line);font-size:.95rem}
        .hs-table th{background:var(--soft2);color:var(--mut);font-size:.74rem;text-transform:uppercase;letter-spacing:.03em}
        .hs-table td.num{text-align:right;font-weight:700}
        .hs-table tr.me{background:#fef9c3}
        .quiz-layout{display:flex;gap:24px;align-items:flex-start}
        .quiz-main{flex:1;min-width:0}
        .quiz-info{width:290px;flex-shrink:0;background:var(--soft2);border:1px solid var(--line);border-radius:16px;padding:20px}
        .quiz-info h2{margin:0 0 6px;font-size:1.15rem}
        .quiz-rules{list-style:none;margin:10px 0 0;padding:0;display:grid;gap:15px}
        .quiz-rules li{display:flex;gap:12px;align-items:flex-start;font-size:.93rem;color:var(--tx);line-height:1.4}
        .quiz-rules .ic{font-size:1.5rem;line-height:1;flex-shrink:0;width:30px;text-align:center}
        @media(max-width:820px){.quiz-layout{flex-direction:column}.quiz-info{width:100%;order:-1}}
        /* Wusstest-du?-Box */
        .wusstest-box{background:linear-gradient(135deg,#fffbeb,#fef3c7);border:1px solid #fcd34d;border-radius:4px;padding:22px 24px;box-shadow:var(--shadow)}
        .wusstest-head{font-weight:800;color:#b45309;font-size:1.05rem;margin-bottom:8px}
        .wusstest-titel{margin:0 0 6px;font-size:1.15rem;color:var(--ink)}
        .wusstest-text{margin:0;color:var(--tx)}
        .wusstest-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:14px;flex-wrap:wrap}
        .wusstest-quelle{font-size:.82rem;color:var(--mut);text-decoration:none}
        .wusstest-quelle:hover{color:var(--pri)}
        .wusstest-next{background:#fff;border:1px solid #fcd34d;color:#b45309;border-radius:9px;padding:8px 14px;font-weight:700;cursor:pointer;font-size:.9rem;transition:.15s}
        .wusstest-next:hover{background:#b45309;color:#fff}
        .pri-cta-block{background:linear-gradient(135deg,#004bb1,#055cc5);color:#fff;border-radius:20px;padding:clamp(22px,4vw,32px);text-align:center;box-shadow:var(--shadow-lg)}
        .pri-cta-block h2{color:#fff;margin-top:0}
        .pri-cta-block p{color:rgba(255,255,255,.9);max-width:54ch;margin:0 auto 18px}

        /* Reveal / Bewegung */
        .reveal{opacity:0;transform:translateY(18px);transition:opacity .6s ease,transform .6s ease}
        .reveal.in{opacity:1;transform:none}
        .reveal-d1{transition-delay:.08s}.reveal-d2{transition-delay:.16s}.reveal-d3{transition-delay:.24s}
        @media(prefers-reduced-motion:reduce){
            *{animation:none!important;scroll-behavior:auto!important}
            .reveal{opacity:1;transform:none;transition:none}
            .card:hover,.cta:hover,.btn:hover{transform:none}
        }

        /* Artikel-Typografie */
        .content{font-size:1.06rem;line-height:1.75}
        .wrap--narrow{max-width:760px;margin-left:auto;margin-right:auto}
        .content h2{margin-top:1.7em}
        .content h3{margin-top:1.3em}
        .content p{margin:0 0 1.1em}
        .content ul,.content ol{margin:0 0 1.1em;padding-left:1.35em}
        .content li{margin:.35em 0}
        .content a{color:var(--pri);text-decoration:underline;text-underline-offset:2px}
        .content img{border-radius:14px;margin:1.2em 0;box-shadow:var(--shadow)}
        .content blockquote{margin:1.2em 0;padding:2px 18px;border-left:4px solid var(--pri-l);color:var(--mut)}
        /* Callout-Boxen für Ratgeber */
        .box{position:relative;border-radius:4px;padding:16px 18px 16px 50px;margin:1.4em 0;border:1px solid var(--line);background:var(--soft2);box-shadow:var(--shadow)}
        .box::before{position:absolute;left:16px;top:15px;font-size:1.2rem;line-height:1}
        .box>strong{display:block;margin-bottom:3px}
        .box-tipp{background:#ecfdf5;border-color:#a7f3d0}.box-tipp::before{content:"💡"}
        .box-info{background:#e8f1fb;border-color:#bcd6f2}.box-info::before{content:"ℹ️"}
        .box-wichtig{background:#fff7ed;border-color:#fed7aa}.box-wichtig::before{content:"⚠️"}
        .box-check{background:#f0fdf4;border-color:#bbf7d0}.box-check::before{content:"✅"}
        .box-kosten{background:#faf5ff;border-color:#e9d5ff}.box-kosten::before{content:"💶"}
        .box-frage{background:#f8fafc;border-color:var(--line)}.box-frage::before{content:"❓"}
        footer.site{border-top:1px solid var(--line);margin-top:56px;padding:32px 0;color:var(--mut);font-size:.9rem;background:var(--soft2)}
        footer.site a{color:var(--mut)}
        footer.site a:hover{color:var(--pri)}
        /* Vertrauens-Badges (Zertifikate) */
        .footer-trust{display:flex;flex-wrap:wrap;justify-content:center;gap:14px 34px;padding-bottom:24px;margin-bottom:24px;border-bottom:1px solid var(--line)}
        .ft-badge{display:flex;align-items:center;gap:11px;color:var(--tx);font-weight:600;line-height:1.25}
        .ft-badge img{height:52px;width:auto;display:block;flex:0 0 auto}
        .ft-badge small{display:block;font-weight:400;color:var(--mut);font-size:.78rem}
        /* Zahlung / Versand + bestehende Infos */
        .footer-cols{display:grid;grid-template-columns:1.5fr 1fr;gap:34px;align-items:start}
        .footer-h{font-size:.95rem;font-weight:700;color:var(--ink);margin:0 0 10px}
        .footer-pay .footer-h{margin-top:18px}
        .footer-pay .footer-h:first-child{margin-top:0}
        .pay-logos{display:flex;flex-wrap:wrap;gap:8px}
        .pay-tile{display:inline-flex;align-items:center;justify-content:center;background:#fff;border:1px solid var(--line);border-radius:var(--r);padding:7px 12px;box-shadow:var(--shadow);height:42px}
        .pay-tile img{height:22px;width:auto;display:block}
        .footer-info p{margin:0 0 .8em}
        @media(max-width:760px){
            .footer-cols{grid-template-columns:1fr;gap:24px}
            .footer-trust{gap:14px 22px}
            .ft-badge img{height:44px}
        }
    </style>

    <x-matomo />
</head>
<body>
<header class="site">
    <div class="wrap">
        @if($brand)
            {{-- Amtsname als Kopf-Titel: bewusst NICHT verlinkt; zugleich die einzige H1 der Seite. --}}
            <div class="brand brand--static"><h1 class="brand-amt">{{ $brand }}</h1></div>
        @else
            <a class="brand" href="{{ url('/') }}">
                <img class="brand-logo" src="{{ asset('img/logo-wkr.svg') }}" alt="{{ config('portal.site_name') }}" width="366" height="80">
            </a>
        @endif
        @unless($brand)
        <div class="header-badges">
            <img src="{{ asset('img/ssl.svg') }}" alt="SSL-Zertifikat" width="48" height="48" loading="lazy">
            <img src="{{ asset('img/din.svg') }}" alt="DIN geprüft" width="48" height="48" loading="lazy">
        </div>
        @endunless
        <div class="header-right">
            <nav class="main{{ count($navLinks) ? ' nav--inpage' : '' }}" id="nav">
                @if(count($navLinks))
                    @foreach($navLinks as $item)
                        <a href="{{ $item['href'] }}">{{ $item['label'] }}</a>
                    @endforeach
                @else
                    <a href="{{ url('/zulassungsstelle') }}">Zulassungsstellen</a>
                    <a href="{{ url('/kennzeichen') }}">Kennzeichen</a>
                    <a href="{{ url('/altkennzeichen') }}">Altkennzeichen</a>
                    <a href="{{ url('/formulare') }}">Formulare</a>
                    <a href="{{ url('/kfz-ratgeber') }}">Ratgeber</a>
                @endif
            </nav>
            <button class="tool-btn js-settings" type="button" title="Darstellung" aria-label="Darstellung einstellen" aria-haspopup="dialog">Aa</button>
            <button class="nav-toggle" aria-label="Menü" aria-expanded="false" onclick="var n=document.getElementById('nav');n.classList.toggle('open');this.setAttribute('aria-expanded',n.classList.contains('open'))">☰</button>
        </div>
    </div>
</header>
<main>
    <div class="wrap">
        {{ $slot }}
    </div>
</main>
<footer class="site">
    <div class="wrap">
        {{-- Vertrauens-/Zertifikats-Badges --}}
        <div class="footer-trust">
            <div class="ft-badge">
                <img src="{{ asset('img/obr.svg') }}" alt="In Deutschland zugelassen" width="52" height="52" loading="lazy">
                <span>In Deutschland zugelassen</span>
            </div>
            <div class="ft-badge">
                <img src="{{ asset('img/din.svg') }}" alt="DIN-zertifiziert" width="52" height="52" loading="lazy">
                <span>DIN-zertifiziert<small>DIN 74069 · Reg. 1M/1295215</small></span>
            </div>
            <div class="ft-badge">
                <img src="{{ asset('img/ssl.svg') }}" alt="Sichere SSL-Verbindung" width="52" height="52" loading="lazy">
                <span>Sichere SSL-Verbindung</span>
            </div>
        </div>

        <div class="footer-cols">
            {{-- Zahlung + Versand --}}
            <div class="footer-pay">
                <h3 class="footer-h">Sichere Zahlung mit</h3>
                <div class="pay-logos">
                    <span class="pay-tile"><img src="{{ asset('img/payment-paypal.svg') }}" alt="PayPal" title="PayPal" loading="lazy"></span>
                    <span class="pay-tile"><img src="{{ asset('img/payment-visa_mastercard.svg') }}" alt="Visa / Mastercard" title="Visa / Mastercard" loading="lazy"></span>
                    <span class="pay-tile"><img src="{{ asset('img/payment-amex.svg') }}" alt="American Express" title="American Express" loading="lazy"></span>
                    <span class="pay-tile"><img src="{{ asset('img/payment-applepay.svg') }}" alt="Apple Pay" title="Apple Pay" loading="lazy"></span>
                    <span class="pay-tile"><img src="{{ asset('img/payment-googlepay.svg') }}" alt="Google Pay" title="Google Pay" loading="lazy"></span>
                    <span class="pay-tile"><img src="{{ asset('img/payment-klarna.svg') }}" alt="Klarna" title="Klarna" loading="lazy"></span>
                    <span class="pay-tile"><img src="{{ asset('img/payment-sofort.svg') }}" alt="Sofort Überweisung" title="Sofort Überweisung" loading="lazy"></span>
                    <span class="pay-tile"><img src="{{ asset('img/payment-rechnung.svg') }}" alt="Kauf auf Rechnung" title="Kauf auf Rechnung" loading="lazy"></span>
                </div>
                <h3 class="footer-h">Versand mit</h3>
                <div class="pay-logos">
                    <span class="pay-tile"><img src="{{ asset('img/shipping-dhl.svg') }}" alt="DHL" title="DHL" loading="lazy"></span>
                    <span class="pay-tile"><img src="{{ asset('img/klimaneutral.svg') }}" alt="Klimaneutraler Versand" title="Klimaneutraler Versand" loading="lazy"></span>
                </div>
            </div>

            {{-- Bestehende Footer-Infos --}}
            <div class="footer-info">
                <p class="muted">Nicht-amtliches Informationsangebot. Die Reservierung erfolgt
                über die zuständige Zulassungsstelle bzw. die externe Reservierungs-App.
                Einige Links sind Partner-/Affiliate-Links (als <em>Anzeige</em> gekennzeichnet).</p>
                <p><a href="{{ url('/ueber-uns') }}">Über uns</a> · <a href="{{ url('/faq') }}">FAQ</a> · <a href="{{ url('/impressum') }}">Impressum</a> · <a href="{{ url('/datenschutz') }}">Datenschutz</a> · <a href="{{ url('/agb') }}">AGB</a></p>
            </div>
        </div>
    </div>
</footer>

<div class="modal-overlay" id="settingsModal" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="settingsTitle">
        <div class="modal-head">
            <h2 id="settingsTitle">Darstellung</h2>
            <button class="modal-close js-settings-close" type="button" aria-label="Schließen">×</button>
        </div>
        <div class="modal-body">
            <div class="set-group">
                <span class="set-label">Modus</span>
                <div class="seg" role="group" aria-label="Farbmodus">
                    <button class="seg-btn js-theme-opt" type="button" data-theme="light">☀️ Hell</button>
                    <button class="seg-btn js-theme-opt" type="button" data-theme="dark">🌙 Dunkel</button>
                    <button class="seg-btn js-theme-opt" type="button" data-theme="auto">🖥️ System</button>
                </div>
            </div>
            <div class="set-group">
                <span class="set-label">Schriftgröße</span>
                <div class="set-font">
                    <button class="tool-btn js-font" type="button" data-d="-1" aria-label="Schrift verkleinern">A−</button>
                    <span class="js-font-val">16 px</span>
                    <button class="tool-btn js-font" type="button" data-d="1" aria-label="Schrift vergrößern">A+</button>
                    <button class="seg-btn js-font-reset" type="button">Zurücksetzen</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/* Scroll-Reveal (Bewegung) */
(function(){
  var els=document.querySelectorAll('.reveal');
  if(!('IntersectionObserver' in window)||!els.length){els.forEach(function(e){e.classList.add('in')});return;}
  var io=new IntersectionObserver(function(en){en.forEach(function(e){if(e.isIntersecting){e.target.classList.add('in');io.unobserve(e.target);}})},{rootMargin:'0px 0px -8% 0px'});
  els.forEach(function(e){io.observe(e)});
})();
/* Lazyload: alle Bilder ohne explizites loading */
document.querySelectorAll('img:not([loading])').forEach(function(i){i.loading='lazy';i.decoding='async';});

/* Header beim Scrollen verkleinern (Logo + Höhe) */
(function(){
  var h=document.querySelector('header.site');
  if(!h)return;
  var small=false;
  function onScroll(){
    var s=(window.scrollY||document.documentElement.scrollTop||0)>30;
    if(s!==small){small=s;h.classList.toggle('is-small',s);}
  }
  window.addEventListener('scroll',onScroll,{passive:true});
  onScroll();
})();

/* Darstellung: Modal mit Theme (hell/dunkel/auto) + Schriftgröße, in localStorage */
(function(){
  var d=document.documentElement, mq=matchMedia('(prefers-color-scheme:dark)');
  function mode(){return localStorage.getItem('theme')||'auto';}
  function applyTheme(m){ if(m==='dark'||(m==='auto'&&mq.matches)) d.setAttribute('data-theme','dark'); else d.removeAttribute('data-theme'); }
  function markTheme(m){ document.querySelectorAll('.js-theme-opt').forEach(function(b){ b.classList.toggle('active', b.getAttribute('data-theme')===m); }); }
  function curFont(){var f=parseInt(localStorage.getItem('fontpx'),10);return (f>=14&&f<=22)?f:16;}
  function showFont(){var el=document.querySelector('.js-font-val'); if(el) el.textContent=curFont()+' px';}

  document.querySelectorAll('.js-theme-opt').forEach(function(b){ b.addEventListener('click',function(){
    var m=b.getAttribute('data-theme'); try{localStorage.setItem('theme',m);}catch(e){} applyTheme(m); markTheme(m);
  }); });
  try{mq.addEventListener('change',function(){ if(mode()==='auto') applyTheme('auto'); });}catch(e){}

  document.querySelectorAll('.js-font').forEach(function(b){ b.addEventListener('click',function(){
    var f=Math.min(22,Math.max(14,curFont()+parseInt(b.getAttribute('data-d'),10)*2));
    d.style.fontSize=f+'px'; try{localStorage.setItem('fontpx',f);}catch(e){} showFont();
  }); });
  var fr=document.querySelector('.js-font-reset');
  if(fr)fr.addEventListener('click',function(){ d.style.fontSize=''; try{localStorage.removeItem('fontpx');}catch(e){} showFont(); });

  var modal=document.getElementById('settingsModal'), trigger=document.querySelector('.js-settings');
  var panel=modal?modal.querySelector('.modal'):null;
  function position(){ if(!trigger||!panel)return; var r=trigger.getBoundingClientRect();
    panel.style.top=(r.bottom+8)+'px'; panel.style.right=Math.max(8,window.innerWidth-r.right)+'px'; }
  function openModal(){ markTheme(mode()); showFont(); modal.hidden=false; position(); }
  function closeModal(){ modal.hidden=true; }
  if(trigger)trigger.addEventListener('click',openModal);
  if(modal){
    modal.addEventListener('click',function(e){ if(e.target===modal) closeModal(); });
    modal.querySelectorAll('.js-settings-close').forEach(function(b){ b.addEventListener('click',closeModal); });
    document.addEventListener('keydown',function(e){ if(e.key==='Escape'&&!modal.hidden) closeModal(); });
    window.addEventListener('resize',function(){ if(!modal.hidden) position(); });
    window.addEventListener('scroll',function(){ if(!modal.hidden) position(); },true);
  }
})();

/* Live-Vorschlagsliste (Autocomplete) für Suchfelder mit [data-suggest] */
(function(){
  var forms=document.querySelectorAll('form[data-suggest]');
  forms.forEach(function(form){
    var input=form.querySelector('input[type="search"],input[name="q"]');
    if(!input) return;
    var endpoint=form.getAttribute('data-suggest');
    var panel=document.createElement('div');
    panel.className='ac-panel'; panel.setAttribute('role','listbox');
    document.body.appendChild(panel);   // an <body> hängen, damit kein overflow:hidden (Hero) das Panel abschneidet
    var items=[], active=-1, timer=null, lastQ='', ctrl=null;

    function close(){panel.classList.remove('open');panel.innerHTML='';items=[];active=-1;input.setAttribute('aria-expanded','false');}
    function go(url){if(url)window.location.href=url;}

    // Panel per fixed-Position unter dem Eingabefeld ausrichten (folgt dem Suchfeld).
    function position(){
      var r=input.getBoundingClientRect(), fr=form.getBoundingClientRect();
      panel.style.top=(r.bottom+6)+'px';
      panel.style.left=fr.left+'px';
      panel.style.width=fr.width+'px';
    }

    function render(list,q){
      position();
      panel.innerHTML=''; items=[]; active=-1;
      list.forEach(function(it){
        var a=document.createElement('a');
        a.className='ac-item'; a.href=it.url; a.setAttribute('role','option');
        var sub=it.kategorie_html?it.kategorie_html:(it.kategorie?escapeHtml(it.kategorie):'');
        a.innerHTML='<strong>'+it.titel_html+'</strong>'+(sub?'<small>'+sub+'</small>':'');
        a.addEventListener('mousedown',function(e){e.preventDefault();go(it.url);});
        panel.appendChild(a); items.push(a);
      });
      var all=document.createElement('a');
      all.className='ac-all'; all.href=form.getAttribute('action')+'?q='+encodeURIComponent(q);
      all.textContent='Alle Treffer für „'+q+'" anzeigen →';
      all.addEventListener('mousedown',function(e){e.preventDefault();go(all.href);});
      panel.appendChild(all); items.push(all);
      panel.classList.add('open'); input.setAttribute('aria-expanded','true');
    }
    function escapeHtml(s){return s.replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});}

    function setActive(i){
      if(!items.length)return;
      if(active>=0&&items[active])items[active].classList.remove('active');
      active=(i+items.length)%items.length;
      items[active].classList.add('active');
      items[active].scrollIntoView({block:'nearest'});
    }

    function fetchSuggest(q){
      if(ctrl)ctrl.abort();
      ctrl=('AbortController'in window)?new AbortController():null;
      fetch(endpoint+'?q='+encodeURIComponent(q),{signal:ctrl?ctrl.signal:undefined,headers:{'Accept':'application/json'}})
        .then(function(r){return r.ok?r.json():[];})
        .then(function(list){
          if(input.value.trim()!==q)return;            // veraltete Antwort verwerfen
          if(!list.length){close();return;}
          render(list,q);
        }).catch(function(){});
    }

    input.setAttribute('autocomplete','off');
    input.setAttribute('role','combobox');
    input.setAttribute('aria-expanded','false');

    input.addEventListener('input',function(){
      var q=input.value.trim();
      if(timer)clearTimeout(timer);
      if(q.length<2){close();return;}
      if(q===lastQ&&panel.classList.contains('open'))return;
      lastQ=q;
      timer=setTimeout(function(){fetchSuggest(q);},160);
    });

    input.addEventListener('keydown',function(e){
      if(!panel.classList.contains('open'))return;
      if(e.key==='ArrowDown'){e.preventDefault();setActive(active+1);}
      else if(e.key==='ArrowUp'){e.preventDefault();setActive(active-1);}
      else if(e.key==='Enter'){if(active>=0&&items[active]){e.preventDefault();go(items[active].href);}}
      else if(e.key==='Escape'){close();}
    });

    input.addEventListener('focus',function(){if(input.value.trim().length>=2&&items.length){position();panel.classList.add('open');}});
    document.addEventListener('click',function(e){if(!form.contains(e.target)&&!panel.contains(e.target))close();});
    window.addEventListener('resize',function(){if(panel.classList.contains('open'))position();});
    window.addEventListener('scroll',function(){if(panel.classList.contains('open'))position();},true);
  });
})();
</script>
</body>
</html>
