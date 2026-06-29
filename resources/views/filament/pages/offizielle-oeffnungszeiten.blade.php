<x-filament-panels::page>
    <div class="mb-4 text-sm text-gray-500 dark:text-gray-400">
        Aus offiziellen Behördenseiten extrahierte Öffnungszeiten zur Prüfung.
        <strong>{{ $zaehler['ok'] }}</strong> bereit zur Übernahme ·
        <strong>{{ $zaehler['uebernommen'] }}</strong> übernommen ·
        <strong>{{ $zaehler['problem'] }}</strong> mit Problem (tot/JS/nicht offiziell).
    </div>

    @php
        $tage = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
        $tab = function (array $woche) use ($tage) {
            $h = '<table class="w-full text-sm"><tbody>';
            foreach ($tage as $t) {
                $z = $woche[$t] ?? [];
                $v = $z ? implode(', ', $z) : '<span class="text-gray-400">geschlossen</span>';
                $h .= '<tr class="border-b border-gray-100 dark:border-gray-800"><th class="py-1 pr-3 text-left font-medium text-gray-500 w-10">'.$t.'</th><td class="py-1 tabular-nums">'.$v.'</td></tr>';
            }
            return $h.'</tbody></table>';
        };
        $badge = [
            'ok' => ['Offiziell extrahiert', 'success'],
            'keine_zeiten' => ['Keine Zeiten (JS/PDF)', 'warning'],
            'unsicher' => ['Unsicher (mehrere Standorte)', 'warning'],
            'nicht_offiziell' => ['Nicht offiziell', 'danger'],
            'fehler' => ['URL tot', 'danger'],
        ];
    @endphp

    <div class="space-y-6">
        @foreach ($eintraege as $e)
            @php($s = $e['stelle'])
            @php($b = $badge[$e['status']] ?? [$e['status'], 'gray'])
            <div wire:key="off-{{ $s->id }}"
                 class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 {{ $e['uebernommen'] ? 'opacity-60' : '' }}">
                <div class="mb-3 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $s->name }}</div>
                        <div class="text-xs text-gray-400">
                            <a href="{{ $e['quelle_url'] }}" target="_blank" class="text-primary-600 hover:underline">{{ \Illuminate\Support\Str::limit($e['quelle_url'], 70) }} ↗</a>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-filament::badge :color="$b[1]">{{ $b[0] }}</x-filament::badge>
                        @if($e['uebernommen'])
                            <x-filament::badge color="success">✓ übernommen</x-filament::badge>
                        @elseif($e['status'] === 'ok' && $e['abweichung'])
                            <x-filament::badge color="warning">weicht von live ab</x-filament::badge>
                        @endif
                    </div>
                </div>

                @if($e['hinweis'])
                    <div class="mb-3 text-xs text-gray-500 dark:text-gray-400">ℹ️ {{ $e['hinweis'] }}</div>
                @endif

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Aktuell live</div>
                        {!! $tab($e['woche_live']) !!}
                    </div>
                    <div class="rounded-lg border p-3 {{ $e['status'] === 'ok' ? 'border-green-300 bg-green-50/40 dark:border-green-800 dark:bg-green-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                        <div class="mb-2 text-xs font-semibold uppercase tracking-wide {{ $e['status'] === 'ok' ? 'text-green-700 dark:text-green-300' : 'text-gray-500' }}">Offiziell (neu)</div>
                        @if($e['hat_off'])
                            {!! $tab($e['woche_off']) !!}
                        @else
                            <div class="text-sm text-gray-400">— keine Zeiten extrahiert —</div>
                        @endif
                    </div>
                </div>

                @if($e['status'] === 'ok' && ! $e['uebernommen'])
                    <button type="button"
                            wire:click="uebernehmen({{ $s->id }})"
                            wire:loading.attr="disabled"
                            class="mt-3 inline-flex items-center justify-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white hover:bg-primary-500 disabled:opacity-50">
                        Offizielle Zeiten übernehmen
                    </button>
                @endif
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
