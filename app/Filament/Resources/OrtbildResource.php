<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrtbildResource\Pages;
use App\Models\Ortbild;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrtbildResource extends Resource
{
    protected static ?string $model = Ortbild::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationLabel = 'Ortsbilder';
    protected static ?string $modelLabel = 'Ortsbild';
    protected static ?string $pluralModelLabel = 'Ortsbilder';
    protected static ?string $navigationGroup = 'Inhalte';

    /**
     * Setzt die Rolle, degradiert das bisherige Bild derselben Rolle je Stadt
     * (dessen lokale Datei wird per Model-Event gelöscht) und lädt das neu
     * gewählte Bild automatisch herunter. Gibt true zurück, wenn der Download
     * erfolgreich war.
     */
    public static function waehle(Ortbild $bild, string $rolle): bool
    {
        // Bisheriges Bild derselben Rolle einzeln freigeben, damit das
        // Model-Event feuert und dessen heruntergeladene Datei entfernt wird.
        Ortbild::where('gemeinde_id', $bild->gemeinde_id)
            ->where('rolle', $rolle)
            ->whereKeyNot($bild->getKey())
            ->get()
            ->each(fn (Ortbild $alt) => $alt->update(['rolle' => 'kandidat']));

        $bild->update(['rolle' => $rolle]);

        return $bild->herunterladen();
    }

    /** Auswahl-Aktion inkl. Rückmeldung (Auto-Download). */
    protected static function auswaehlen(Ortbild $bild, string $rolle, string $label): void
    {
        $ok = static::waehle($bild, $rolle);
        Notification::make()
            ->title($ok ? "{$label} gesetzt & heruntergeladen" : "{$label} gesetzt – Download fehlgeschlagen")
            ->body($ok ? null : 'Bild später erneut auswählen oder „Auswahl herunterladen" nutzen.')
            ->{$ok ? 'success' : 'warning'}()
            ->send();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Placeholder::make('vorschau')
                ->label('Vorschau')
                ->content(fn (?Ortbild $record) => $record?->vorschauUrl(800)
                    ? new \Illuminate\Support\HtmlString(
                        '<img src="'.e($record->vorschauUrl(800)).'" style="max-height:220px;border-radius:8px">')
                    : '—')
                ->columnSpanFull(),
            Forms\Components\Select::make('rolle')
                ->options(array_combine(Ortbild::ROLLEN, ['Kandidat', 'Hero (oben)', 'Footer 1 (unten)', 'Footer 2 (unten)', 'Abgelehnt']))
                ->required()->native(false),
            Forms\Components\Toggle::make('bearbeitet')->label('Bild bearbeitet (Zuschnitt/Anpassung)'),
            Forms\Components\TextInput::make('titel')->maxLength(512)->columnSpanFull(),
            Forms\Components\TextInput::make('autor')->maxLength(255),
            Forms\Components\TextInput::make('autor_url')->label('Autor-URL')->url()->maxLength(512),
            Forms\Components\TextInput::make('lizenz')->maxLength(48),
            Forms\Components\TextInput::make('lizenz_url')->label('Lizenz-URL')->url()->maxLength(255),
            Forms\Components\TextInput::make('quelle')->label('Quelle (Original-Seite)')->url()->maxLength(512)->columnSpanFull(),
            Forms\Components\TextInput::make('src')->label('Lokaler Pfad (nach Download)')->maxLength(512)->columnSpanFull()
                ->helperText('Wird von „ortbilder:download“ gesetzt. Leer = Flickr-Direktlink wird ausgeliefert.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->groups([
                Tables\Grouping\Group::make('gemeinde.name')->label('Stadt')->collapsible(),
            ])
            ->defaultGroup('gemeinde.name')
            ->columns([
                Tables\Columns\ImageColumn::make('vorschau')->label('Bild')
                    ->getStateUsing(fn (Ortbild $r) => $r->vorschauUrl(480))
                    ->height(56)->width(84)   // feste Box – verhindert, dass breite Panoramen die Aktionen wegschieben
                    ->extraImgAttributes(['loading' => 'lazy', 'class' => 'ortbild-thumb',
                        'style' => 'object-fit:cover;border-radius:6px']),
                Tables\Columns\TextColumn::make('gemeinde.name')->label('Stadt')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('wahrzeichen')->label('Motiv')->wrap()->limit(40)->toggleable(),
                Tables\Columns\TextColumn::make('rolle')->badge()->color(fn ($state) => match ($state) {
                    'hero' => 'success', 'footer' => 'info', 'footer2' => 'warning', 'abgelehnt' => 'danger', default => 'gray',
                })->formatStateUsing(fn ($state) => match ($state) {
                    'hero' => 'Hero', 'footer' => 'Footer 1', 'footer2' => 'Footer 2', 'abgelehnt' => 'Abgelehnt', default => 'Kandidat',
                }),
                Tables\Columns\TextColumn::make('lizenz')->badge()->color('gray')->toggleable(),
                Tables\Columns\TextColumn::make('provider')->label('Quelle')->badge()->color('gray')->toggleable(),
                Tables\Columns\TextColumn::make('autor')->limit(24)->toggleable()->toggledHiddenByDefault(true),
                Tables\Columns\TextColumn::make('width')->label('px')->formatStateUsing(fn ($state, $r) => $state ? $state.'×'.$r->height : '–')
                    ->toggleable()->toggledHiddenByDefault(true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gemeinde')->relationship('gemeinde', 'name')->searchable()->preload(),
                Tables\Filters\SelectFilter::make('rolle')
                    ->options(array_combine(Ortbild::ROLLEN, ['Kandidat', 'Hero', 'Footer 1', 'Footer 2', 'Abgelehnt'])),
            ])
            ->actions([
                Tables\Actions\Action::make('hero')->label('Hero')->icon('heroicon-o-star')->color('success')->button()
                    ->visible(fn (Ortbild $r) => $r->rolle !== 'hero')
                    ->action(fn (Ortbild $r) => static::auswaehlen($r, 'hero', 'Hero')),
                Tables\Actions\Action::make('footer')->label('Footer 1')->icon('heroicon-o-arrow-down-circle')->color('info')->button()
                    ->visible(fn (Ortbild $r) => $r->rolle !== 'footer')
                    ->action(fn (Ortbild $r) => static::auswaehlen($r, 'footer', 'Footer 1')),
                Tables\Actions\Action::make('footer2')->label('Footer 2')->icon('heroicon-o-arrow-down-circle')->color('warning')->button()
                    ->visible(fn (Ortbild $r) => $r->rolle !== 'footer2')
                    ->action(fn (Ortbild $r) => static::auswaehlen($r, 'footer2', 'Footer 2')),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('original')->label('Original ansehen')->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn (Ortbild $r) => $r->quelle, true)->visible(fn (Ortbild $r) => (bool) $r->quelle),
                    Tables\Actions\EditAction::make()->label('Bearbeiten'),
                    Tables\Actions\Action::make('ablehnen')->label('Ablehnen')->icon('heroicon-o-x-mark')->color('danger')
                        ->visible(fn (Ortbild $r) => $r->rolle !== 'abgelehnt')
                        ->action(fn (Ortbild $r) => $r->update(['rolle' => 'abgelehnt'])),
                ]),
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
            'index' => Pages\ListOrtbilder::route('/'),
            'edit'  => Pages\EditOrtbild::route('/{record}/edit'),
        ];
    }
}
