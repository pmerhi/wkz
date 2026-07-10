<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RatgeberArtikelResource\Pages;
use App\Filament\Resources\RatgeberArtikelResource\RelationManagers;
use App\Models\RatgeberArtikel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RatgeberArtikelResource extends Resource
{
    protected static ?string $model = RatgeberArtikel::class;

    protected static ?string $modelLabel = 'Ratgeber-Artikel';

    protected static ?string $pluralModelLabel = 'Ratgeber-Artikel';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('titel')
                    ->tel()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('kategorie_id')
                    ->relationship('kategorie', 'name'),
                Forms\Components\Textarea::make('intro')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('body')
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('stand_datum'),
                Forms\Components\Textarea::make('quelle')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('published_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titel')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('kategorie.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stand_datum')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
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
            'index' => Pages\ListRatgeberArtikels::route('/'),
            'create' => Pages\CreateRatgeberArtikel::route('/create'),
            'edit' => Pages\EditRatgeberArtikel::route('/{record}/edit'),
        ];
    }
}
