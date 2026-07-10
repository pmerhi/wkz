<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KennzeichenKuerzelResource\Pages;
use App\Filament\Resources\KennzeichenKuerzelResource\RelationManagers;
use App\Models\KennzeichenKuerzel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KennzeichenKuerzelResource extends Resource
{
    protected static ?string $model = KennzeichenKuerzel::class;

    protected static ?string $modelLabel = 'Kennzeichen-Kürzel';

    protected static ?string $pluralModelLabel = 'Kennzeichen-Kürzel';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(3),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('bedeutung')
                    ->label('Bedeutung (aktueller Zulassungsbezirk)')
                    ->maxLength(255),
                Forms\Components\Toggle::make('ist_altkennzeichen')
                    ->label('Altkennzeichen (wieder eingeführt)')
                    ->helperText('Seit der Kennzeichenliberalisierung (ab 01.11.2012) wieder erhältlich.')
                    ->live(),
                Forms\Components\TextInput::make('historische_stadt')
                    ->label('Historische Bedeutung (Stadt/Kreis)')
                    ->maxLength(255)
                    ->visible(fn (Forms\Get $get) => $get('ist_altkennzeichen')),
                Forms\Components\TextInput::make('bedeutung_quelle')
                    ->label('Datenherkunft Bedeutung')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bedeutung')
                    ->searchable(),
                Tables\Columns\IconColumn::make('ist_altkennzeichen')
                    ->label('Alt')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('historische_stadt')
                    ->label('Historisch')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('ist_altkennzeichen')
                    ->label('Altkennzeichen'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKennzeichenKuerzels::route('/'),
            'create' => Pages\CreateKennzeichenKuerzel::route('/create'),
            'edit' => Pages\EditKennzeichenKuerzel::route('/{record}/edit'),
        ];
    }
}
