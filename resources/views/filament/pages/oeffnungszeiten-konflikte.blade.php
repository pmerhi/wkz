<x-filament-panels::page>
    <div class="mb-4 text-sm text-gray-500 dark:text-gray-400">
        <strong>{{ $anzahl }}</strong> Zulassungsstellen mit widersprüchlichen Öffnungszeiten
        (überlappende Intervalle am selben Tag). Wähle je Stelle die korrekte Variante aus
        <em>wunschkennzeichen-reservieren.de</em> oder <em>kennzeichenking.de</em>.
        Nach dem Übernehmen verschwindet die Stelle aus der Liste.
    </div>

    @if ($anzahl === 0)
        <div class="rounded-xl border border-green-200 bg-green-50 p-6 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">
            ✅ Keine Konflikte mehr offen.
        </div>
    @endif

    @php
        $tage = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

        $wocheTabelle = function (array $woche) use ($tage) {
            $html = '<table class="w-full text-sm"><tbody>';
            foreach ($tage as $t) {
                $zeiten = $woche[$t] ?? [];
                $wert = $zeiten ? implode(', ', $zeiten) : '<span class="text-gray-400">geschlossen</span>';
                $html .= '<tr class="border-b border-gray-100 dark:border-gray-800">'
                    . '<th class="py-1 pr-3 text-left font-medium text-gray-500 w-10">' . $t . '</th>'
                    . '<td class="py-1 tabular-nums">' . $wert . '</td></tr>';
            }
            return $html . '</tbody></table>';
        };
    @endphp

    <div class="space-y-6">
        @foreach ($stellen as $eintrag)
            @php $s = $eintrag['stelle']; @endphp
            <div
                wire:key="stelle-{{ $s->id }}"
                class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900"
            >
                <div class="mb-3 flex items-start justify-between gap-3">
                    <div>
                        <div class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $s->name }}</div>
                        <div class="text-xs text-gray-400">
                            {{ $s->strasse }}{{ $s->strasse ? ', ' : '' }}{{ $s->plz }} {{ $s->ort }}
                            · <a href="{{ $s->url() }}" target="_blank" class="text-primary-600 hover:underline">Seite ansehen ↗</a>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                    {{-- Aktueller (konfliktbehafteter) Stand zur Referenz --}}
                    <div class="rounded-lg border border-amber-200 bg-amber-50/60 p-3 dark:border-amber-800 dark:bg-amber-900/20">
                        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">
                            Aktuell gespeichert (Konflikt)
                        </div>
                        {!! $wocheTabelle($eintrag['aktuell']) !!}
                    </div>

                    {{-- Kandidaten aus beiden Quellen --}}
                    @forelse ($eintrag['kandidaten'] as $k)
                        <div class="flex flex-col rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <div>
                                    <div class="text-xs font-semibold text-gray-900 dark:text-gray-100">{{ $k['quelle'] }}</div>
                                    @if ($k['badge'])
                                        <div class="text-[11px] text-gray-400">{{ $k['badge'] }}</div>
                                    @endif
                                </div>
                            </div>
                            <div class="grow">
                                {!! $wocheTabelle($k['woche']) !!}
                            </div>
                            <button
                                type="button"
                                wire:click="uebernehmen({{ $s->id }}, {{ \Illuminate\Support\Js::from($k['flat']) }}, {{ \Illuminate\Support\Js::from($k['quelle']) }})"
                                wire:loading.attr="disabled"
                                class="mt-3 inline-flex items-center justify-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white hover:bg-primary-500 disabled:opacity-50"
                            >
                                Diese übernehmen
                            </button>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-300 p-3 text-sm text-gray-400 dark:border-gray-600">
                            Keine Quelldaten gefunden – bitte in der Zulassungsstelle manuell korrigieren.
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
