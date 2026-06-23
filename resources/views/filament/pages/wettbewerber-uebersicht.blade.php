<x-filament-panels::page>
    <div class="mb-4 text-sm text-gray-500 dark:text-gray-400">
        Internes Wettbewerber-Dashboard ({{ count($rows) }} Anbieter) ·
        Archiv gesamt: <strong>{{ $seitenGesamt }}</strong> Seiten ·
        Extrahierte Stellen: <strong>{{ $extrakteGesamt }}</strong>.
        <span class="text-amber-600">Interne Analyse — keine Veröffentlichung ohne anwaltliche Freigabe.</span>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($rows as $r)
            @php $w = $r['w']; @endphp
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="text-xs text-gray-400">#{{ $w->rang }} · {{ $w->typ }}</div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $w->name }}</h3>
                        <a href="https://{{ $w->domain }}" target="_blank" rel="noopener"
                           class="text-sm text-primary-600 hover:underline">{{ $w->domain }} ↗</a>
                    </div>
                    <a href="{{ $r['editUrl'] }}" class="text-xs text-gray-500 hover:underline">bearbeiten</a>
                </div>

                @if ($w->betreiber)
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $w->betreiber }}</p>
                @endif

                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <span class="rounded bg-gray-100 px-2 py-1 dark:bg-gray-800">Archiv: <strong>{{ $w->crawl_seiten_count }}</strong></span>
                    <span class="rounded bg-gray-100 px-2 py-1 dark:bg-gray-800">Stellen: <strong>{{ $w->extrakte_count }}</strong></span>
                    @if ($w->extrakte_count)
                        <span class="rounded bg-green-100 px-2 py-1 text-green-800 dark:bg-green-900/40 dark:text-green-300">AGS: <strong>{{ $w->extrakte_ags_count }}/{{ $w->extrakte_count }}</strong></span>
                    @endif
                </div>

                @if ($w->dedup_hinweis)
                    <p class="mt-3 line-clamp-3 text-xs text-gray-500 dark:text-gray-400" title="{{ $w->dedup_hinweis }}">
                        <span class="font-medium text-gray-600 dark:text-gray-300">Erkenntnis:</span> {{ $w->dedup_hinweis }}
                    </p>
                @endif

                <div class="mt-4 flex flex-wrap gap-3 text-sm">
                    <a href="{{ $r['archivUrl'] }}" class="text-primary-600 hover:underline">→ Seiten-Archiv</a>
                    @if ($w->extrakte_count)
                        <a href="{{ $r['extraktUrl'] }}" class="text-primary-600 hover:underline">→ Extrakte</a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
