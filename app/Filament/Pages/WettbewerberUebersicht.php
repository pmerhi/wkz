<?php

namespace App\Filament\Pages;

use App\Filament\Resources\CrawlSeiteResource;
use App\Filament\Resources\ExtraktZulassungsstelleResource;
use App\Filament\Resources\WettbewerberResource;
use App\Models\Wettbewerber;
use Filament\Pages\Page;

class WettbewerberUebersicht extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Markt';
    protected static ?string $navigationLabel = 'Wettbewerber-Übersicht';
    protected static ?int $navigationSort = -1;
    protected static ?string $title = 'Wettbewerber-Übersicht';

    protected static string $view = 'filament.pages.wettbewerber-uebersicht';

    protected function getViewData(): array
    {
        $rows = Wettbewerber::query()
            ->withCount([
                'crawlSeiten',
                'extrakte',
                'extrakte as extrakte_ags_count' => fn ($q) => $q->whereNotNull('gemeinde_id'),
            ])
            ->orderBy('rang')
            ->get()
            ->map(fn (Wettbewerber $w) => [
                'w'          => $w,
                'archivUrl'  => CrawlSeiteResource::getUrl('index', ['tableFilters' => ['wettbewerber' => ['value' => $w->id]]]),
                'extraktUrl' => ExtraktZulassungsstelleResource::getUrl('index', ['tableFilters' => ['wettbewerber' => ['value' => $w->id]]]),
                'editUrl'    => WettbewerberResource::getUrl('edit', ['record' => $w]),
            ]);

        return [
            'rows'           => $rows,
            'seitenGesamt'   => \App\Models\CrawlSeite::count(),
            'extrakteGesamt' => \App\Models\ExtraktZulassungsstelle::count(),
        ];
    }
}
