@props([
    'title' => null,
    'description' => '',
    'canonical' => null,
    'robots' => 'index,follow',
    'ogType' => 'website',
    'schemas' => [],
])
@php $ogImage = config('portal.og_image'); @endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('portal.site_name') }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="{{ $robots }}">
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">

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
        :root { --akz:#0b5; --tx:#1a1a1a; --mut:#666; --bg:#fff; --line:#e6e6e6; }
        * { box-sizing:border-box; }
        body { margin:0; font:16px/1.6 -apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif; color:var(--tx); background:var(--bg); }
        header.site { border-bottom:1px solid var(--line); }
        .wrap { max-width:960px; margin:0 auto; padding:0 20px; }
        header.site .wrap { display:flex; align-items:center; justify-content:space-between; height:64px; }
        header.site a.brand { font-weight:700; text-decoration:none; color:var(--tx); }
        nav.main a { text-decoration:none; color:var(--tx); margin-left:18px; }
        main { padding:32px 0; }
        h1 { font-size:1.9rem; line-height:1.2; margin:.2em 0 .5em; }
        h2 { font-size:1.3rem; margin:1.4em 0 .4em; }
        .cta { display:inline-block; background:var(--akz); color:#fff; padding:12px 22px; border-radius:8px; text-decoration:none; font-weight:600; }
        .muted { color:var(--mut); }
        .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:12px; }
        .card { border:1px solid var(--line); border-radius:10px; padding:14px; }
        .card a { text-decoration:none; color:var(--tx); font-weight:600; }
        nav.breadcrumb { font-size:.9rem; margin-bottom:8px; }
        nav.breadcrumb a { color:var(--mut); text-decoration:none; }
        table.info { border-collapse:collapse; width:100%; }
        table.info th, table.info td { text-align:left; padding:6px 0; border-bottom:1px solid var(--line); vertical-align:top; }
        table.info th { width:170px; color:var(--mut); font-weight:500; }
        footer.site { border-top:1px solid var(--line); margin-top:48px; padding:24px 0; color:var(--mut); font-size:.9rem; }
        .badge { display:inline-block; border:1px solid var(--line); border-radius:6px; padding:2px 8px; margin:2px; font-size:.9rem; text-decoration:none; color:var(--tx); }
        .badge-alt { border-color:#1d4ed8; background:#eff6ff; color:#1d4ed8; font-weight:600; }
    </style>

    <x-matomo />
</head>
<body>
<header class="site">
    <div class="wrap">
        <a class="brand" href="{{ url('/') }}">Wunschkennzeichen-Portal</a>
        <nav class="main">
            <a href="{{ url('/zulassungsstelle') }}">Zulassungsstellen</a>
            <a href="{{ url('/kennzeichen') }}">Kennzeichen</a>
            <a href="{{ url('/altkennzeichen') }}">Altkennzeichen</a>
            <a href="{{ url('/ratgeber') }}">Ratgeber</a>
        </nav>
    </div>
</header>
<main>
    <div class="wrap">
        {{ $slot }}
    </div>
</main>
<footer class="site">
    <div class="wrap">
        <p class="muted">Nicht-amtliches Informationsangebot. Die Reservierung erfolgt
        über die zuständige Zulassungsstelle bzw. die externe Reservierungs-App.
        Einige Links sind Partner-/Affiliate-Links (als <em>Anzeige</em> gekennzeichnet).</p>
        <p><a href="{{ url('/ueber-uns') }}">Über uns</a> · <a href="{{ url('/impressum') }}">Impressum</a> · <a href="{{ url('/datenschutz') }}">Datenschutz</a></p>
    </div>
</footer>
</body>
</html>
