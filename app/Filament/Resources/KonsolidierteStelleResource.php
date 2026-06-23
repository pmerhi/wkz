<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KonsolidierteStelleResource\Pages;
use App\Filament\Resources\KonsolidierteStelleResource\RelationManagers;
use App\Models\KonsolidierteStelle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KonsolidierteStelleResource extends Resource
{
    protected static ?string $model = KonsolidierteStelle::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Markt';
    protected static ?string $navigationLabel = 'Konsolidierte Stellen';
    protected static ?string $modelLabel = 'Konsolidierte Stelle';
    protected static ?string $pluralModelLabel = 'Konsolidierte Stellen';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('identitaet')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('strasse')
                    ->maxLength(255),
                Forms\Components\TextInput::make('plz')
                    ->maxLength(10),
                Forms\Components\TextInput::make('ort')
                    ->maxLength(255),
                Forms\Components\TextInput::make('telefon')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('website')
                    ->maxLength(255),
                Forms\Components\TextInput::make('oeffnungszeiten'),
                Forms\Components\Select::make('gemeinde_id')
                    ->relationship('gemeinde', 'name'),
                Forms\Components\Select::make('kreis_id')
                    ->relationship('kreis', 'name'),
                Forms\Components\TextInput::make('quellen'),
                Forms\Components\TextInput::make('quellen_anzahl')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('identitaet')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('strasse')
                    ->searchable(),
                Tables\Columns\TextColumn::make('plz')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ort')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('website')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gemeinde.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kreis.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quellen_anzahl')
                    ->numeric()
                    ->sortable(),
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
                //
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
            'index' => Pages\ListKonsolidierteStelles::route('/'),
            'create' => Pages\CreateKonsolidierteStelle::route('/create'),
            'edit' => Pages\EditKonsolidierteStelle::route('/{record}/edit'),
        ];
    }
}
