<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnrichmentIdeaResource\Pages;
use App\Models\EnrichmentIdea;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EnrichmentIdeaResource extends Resource
{
    protected static ?string $model = EnrichmentIdea::class;

    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';
    protected static ?string $navigationLabel = 'Ideen-Funde';
    protected static ?string $modelLabel = 'Idee';
    protected static ?string $pluralModelLabel = 'Ideen-Funde';
    protected static ?string $navigationGroup = 'Recherche';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'neu')->count() ?: null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('titel')->required()->maxLength(255)->columnSpanFull(),
            Forms\Components\Select::make('kategorie')->options([
                'Daten' => 'Daten/Statistik', 'Lokal' => 'Lokaler Bezug', 'Interaktiv' => 'Interaktiv/Tool',
                'UGC' => 'Nutzer-Inhalte (UGC)', 'Wettbewerb' => 'Wettbewerber-Feature',
                'Wusstest' => 'Wusstest du? (Fakt)', 'Sonstiges' => 'Sonstiges',
            ])->native(false),
            Forms\Components\Select::make('status')->options(array_combine(EnrichmentIdea::STATUS, EnrichmentIdea::STATUS))
                ->default('neu')->required()->native(false),
            Forms\Components\Textarea::make('beschreibung')->rows(3)->columnSpanFull(),
            Forms\Components\Textarea::make('umsetzung')->label('Umsetzungsvorschlag')->rows(2)->columnSpanFull(),
            Forms\Components\TextInput::make('wettbewerber')->maxLength(255),
            Forms\Components\TextInput::make('quelle')->label('Quelle/Fund-URL')->url()->maxLength(512),
            Forms\Components\TextInput::make('seo_wert')->numeric()->minValue(1)->maxValue(5)->default(3)->helperText('1–5'),
            Forms\Components\TextInput::make('relevanz')->numeric()->minValue(1)->maxValue(5)->default(3)->helperText('Themenbezug 1–5'),
            Forms\Components\TextInput::make('aufwand')->numeric()->minValue(1)->maxValue(5)->default(3)->helperText('1=klein … 5=groß'),
            Forms\Components\Textarea::make('notiz')->label('Kuratierungs-Notiz')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('score', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('titel')->searchable()->wrap()->limit(70)->weight('bold'),
                Tables\Columns\TextColumn::make('kategorie')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('score')->sortable()->badge()
                    ->color(fn ($state) => $state >= 6 ? 'success' : ($state >= 3 ? 'warning' : 'gray')),
                Tables\Columns\TextColumn::make('seo_wert')->label('SEO')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('relevanz')->label('Rel.')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('aufwand')->label('Aufw.')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn ($state) => match ($state) {
                    'umsetzen' => 'success', 'umgesetzt' => 'info', 'abgelehnt' => 'danger',
                    'geprueft' => 'warning', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('wettbewerber')->toggleable()->limit(24),
                Tables\Columns\TextColumn::make('created_at')->date('d.m.Y')->sortable()->label('Gefunden'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(EnrichmentIdea::STATUS, EnrichmentIdea::STATUS)),
                Tables\Filters\SelectFilter::make('kategorie')->options([
                    'Daten' => 'Daten', 'Lokal' => 'Lokal', 'Interaktiv' => 'Interaktiv',
                    'UGC' => 'UGC', 'Wettbewerb' => 'Wettbewerb',
                    'Wusstest' => 'Wusstest du?', 'Sonstiges' => 'Sonstiges',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('umsetzen')->icon('heroicon-o-check')->color('success')
                    ->visible(fn (EnrichmentIdea $r) => ! in_array($r->status, ['umsetzen', 'umgesetzt']))
                    ->action(fn (EnrichmentIdea $r) => $r->update(['status' => 'umsetzen'])),
                Tables\Actions\Action::make('ablehnen')->icon('heroicon-o-x-mark')->color('danger')->requiresConfirmation()
                    ->visible(fn (EnrichmentIdea $r) => $r->status !== 'abgelehnt')
                    ->action(fn (EnrichmentIdea $r) => $r->update(['status' => 'abgelehnt'])),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEnrichmentIdeas::route('/'),
            'create' => Pages\CreateEnrichmentIdea::route('/create'),
            'edit'   => Pages\EditEnrichmentIdea::route('/{record}/edit'),
        ];
    }
}
