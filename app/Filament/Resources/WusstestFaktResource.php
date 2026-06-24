<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WusstestFaktResource\Pages;
use App\Models\WusstestFakt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WusstestFaktResource extends Resource
{
    protected static ?string $model = WusstestFakt::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = 'Wusstest du?';
    protected static ?string $modelLabel = 'Wusstest-Fakt';
    protected static ?string $pluralModelLabel = 'Wusstest-Fakten';
    protected static ?string $navigationGroup = 'Recherche';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'neu')->count() ?: null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('titel')->required()->maxLength(255)->columnSpanFull(),
            Forms\Components\Textarea::make('beschreibung')->label('Fakt')->rows(3)->columnSpanFull(),
            Forms\Components\TextInput::make('quelle')->label('Quelle/Beleg-URL')->url()->maxLength(512)->columnSpanFull(),
            Forms\Components\Select::make('status')->options(array_combine(WusstestFakt::STATUS, WusstestFakt::STATUS))
                ->default('neu')->required()->native(false),
            Forms\Components\Textarea::make('notiz')->label('Notiz')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('titel')->searchable()->weight('bold')->wrap(),
                Tables\Columns\TextColumn::make('beschreibung')->label('Fakt')->limit(90)->wrap()->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn ($state) => match ($state) {
                    'umsetzen' => 'success', 'umgesetzt' => 'info', 'abgelehnt' => 'danger',
                    'geprueft' => 'warning', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('quelle')->url(fn ($record) => $record->quelle, true)
                    ->limit(28)->color('primary')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('Gefunden')->date('d.m.Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(WusstestFakt::STATUS, WusstestFakt::STATUS)),
            ])
            ->actions([
                Tables\Actions\Action::make('umsetzen')->icon('heroicon-o-check')->color('success')
                    ->visible(fn (WusstestFakt $r) => ! in_array($r->status, ['umsetzen', 'umgesetzt']))
                    ->action(fn (WusstestFakt $r) => $r->update(['status' => 'umsetzen'])),
                Tables\Actions\Action::make('ablehnen')->icon('heroicon-o-x-mark')->color('danger')->requiresConfirmation()
                    ->visible(fn (WusstestFakt $r) => $r->status !== 'abgelehnt')
                    ->action(fn (WusstestFakt $r) => $r->update(['status' => 'abgelehnt'])),
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
            'index'  => Pages\ListWusstestFakts::route('/'),
            'create' => Pages\CreateWusstestFakt::route('/create'),
            'edit'   => Pages\EditWusstestFakt::route('/{record}/edit'),
        ];
    }
}
