@props(['kreis' => null])
@php $s = $kreis?->statistik; @endphp
@if($s && $s->hatDaten())
    @php
        $titel = $kreis->name ? 'Daten &amp; Fakten: '.e($kreis->name) : 'Daten &amp; Fakten zur Region';
        $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
        $ladeGesamt = (int) $s->ladepunkte_normal + (int) $s->ladepunkte_schnell;
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
            @if($s->elektro_pkw && $s->pkw_bestand)
                <div class="card"><div class="muted" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em">E-Auto-Anteil</div>
                    <div style="font-size:1.5rem;font-weight:800;color:var(--ink)">{{ number_format($s->elektro_pkw / $s->pkw_bestand * 100, 1, ',', '.') }} %</div>
                    <div class="muted" style="font-size:.78rem">{{ number_format($s->elektro_pkw, 0, ',', '.') }} E-Pkw</div></div>
            @endif
            @if($s->flaeche_km2)
                <div class="card"><div class="muted" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em">Fläche</div>
                    <div style="font-size:1.5rem;font-weight:800;color:var(--ink)">{{ $fmt($s->flaeche_km2) }} km²</div></div>
            @endif
            @if($ladeGesamt)
                <div class="card"><div class="muted" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em">Öffentl. Ladepunkte</div>
                    <div style="font-size:1.5rem;font-weight:800;color:var(--ink)">{{ $fmt($ladeGesamt) }}</div>
                    @if($s->ladepunkte_schnell)<div class="muted" style="font-size:.78rem">davon {{ $fmt($s->ladepunkte_schnell) }} Schnelllader</div>@endif</div>
            @endif
            @if($s->auspendler_quote)
                <div class="card"><div class="muted" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em">Auspendlerquote</div>
                    <div style="font-size:1.5rem;font-weight:800;color:var(--ink)">{{ number_format($s->auspendler_quote, 1, ',', '.') }} %</div></div>
            @endif
        </div>
        @if($s->auspendler_quote || $s->pendler_saldo !== null)
            <p class="box box-info" style="margin-top:14px">
                🚗 <strong>Mobilität in {{ $kreis->name ?: 'der Region' }}:</strong>
                @if($s->auspendler_quote){{ number_format($s->auspendler_quote, 1, ',', '.') }} % der Beschäftigten pendeln zur Arbeit aus dem Kreis aus.@endif
                @if($s->pendler_saldo !== null && $s->pendler_saldo != 0)
                    Der Pendlersaldo ist mit {{ ($s->pendler_saldo > 0 ? '+' : '').number_format($s->pendler_saldo, 0, ',', '.') }}
                    {{ $s->pendler_saldo > 0 ? 'positiv – es pendeln mehr Menschen zur Arbeit herein als heraus (Arbeitsort-Region).' : 'negativ – es pendeln mehr Menschen zur Arbeit hinaus als herein (Wohnort-Region).' }}
                @endif
                Bundesweit nutzen rund zwei Drittel der Pendler das Auto – ein Grund für die hohe Bedeutung von Kfz-Zulassung und Wunschkennzeichen vor Ort.
                <span class="muted" style="font-size:.82rem">Quelle: Pendleratlas der statistischen Ämter, {{ $s->pendler_stand }}.</span>
            </p>
        @endif
        @if($ladeGesamt)
            <p class="box box-info" style="margin-top:14px">
                ⚡ <strong>{{ $fmt($ladeGesamt) }} öffentliche Ladepunkte</strong> in {{ $kreis->name ?: 'der Region' }}.
                Wer elektrisch fährt, bekommt mit dem <a href="{{ url('/kfz-ratgeber/e-kennzeichen') }}">E-Kennzeichen</a>
                vielerorts Vorteile (z.&nbsp;B. kostenloses Parken oder Busspur-Nutzung – je nach Kommune).
                <span class="muted" style="font-size:.82rem">Quelle: Bundesnetzagentur-Ladesäulenregister, Stand {{ $s->ladepunkte_stand }}.</span>
            </p>
        @endif
        <p class="muted" style="font-size:.82rem;margin-top:10px">
            Quelle: {{ $s->quelle ?: 'amtliche Statistik' }}@if($s->stand_jahr) · Stand {{ $s->stand_jahr }}@endif. Angaben ohne Gewähr.
        </p>
    </section>
@endif
