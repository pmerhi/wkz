@props(['kreis' => null])
@php $s = $kreis?->statistik; @endphp
@if($s && $s->hatDaten())
    @php
        $titel = $kreis->name ? 'Daten &amp; Fakten: '.e($kreis->name) : 'Daten &amp; Fakten zur Region';
        $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    @endphp
    <section class="section reveal" id="daten">
        <h2>{!! $titel !!}</h2>
        <div class="grid">
            @if($s->einwohner)
                <div class="card"><div class="muted" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em">Einwohner</div>
                    <div style="font-size:1.5rem;font-weight:800;color:var(--ink)">{{ $fmt($s->einwohner) }}</div></div>
            @endif
            @if($s->kfz_bestand)
                <div class="card"><div class="muted" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em">Zugelassene Kfz</div>
                    <div style="font-size:1.5rem;font-weight:800;color:var(--ink)">{{ $fmt($s->kfz_bestand) }}</div></div>
            @endif
            @if($s->pkw_bestand)
                <div class="card"><div class="muted" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em">davon Pkw</div>
                    <div style="font-size:1.5rem;font-weight:800;color:var(--ink)">{{ $fmt($s->pkw_bestand) }}</div></div>
            @endif
            @if($s->pkw_dichte)
                <div class="card"><div class="muted" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em">Pkw je 1.000 Einw.</div>
                    <div style="font-size:1.5rem;font-weight:800;color:var(--ink)">{{ number_format($s->pkw_dichte, 0, ',', '.') }}</div></div>
            @endif
            @if($s->flaeche_km2)
                <div class="card"><div class="muted" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em">Fläche</div>
                    <div style="font-size:1.5rem;font-weight:800;color:var(--ink)">{{ $fmt($s->flaeche_km2) }} km²</div></div>
            @endif
        </div>
        <p class="muted" style="font-size:.82rem;margin-top:10px">
            Quelle: {{ $s->quelle ?: 'amtliche Statistik' }}@if($s->stand_jahr) · Stand {{ $s->stand_jahr }}@endif. Angaben ohne Gewähr.
        </p>
    </section>
@endif
