<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ZulassungsstelleResource\Pages;
use App\Filament\Resources\ZulassungsstelleResource\RelationManagers;
use App\Models\Zulassungsstelle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ZulassungsstelleResource extends Resource
{
    protected static ?string $model = Zulassungsstelle::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('traeger')
                    ->maxLength(255),
                Forms\Components\TextInput::make('strasse')
                    ->maxLength(255),
                Forms\Components\TextInput::make('plz')
                    ->maxLength(10),
                Forms\Components\TextInput::make('ort')
                    ->maxLength(255),
                Forms\Components\Select::make('bundesland_id')
                    ->relationship('bundesland', 'name'),
                Forms\Components\TextInput::make('lat')
                    ->numeric(),
                Forms\Components\TextInput::make('lng')
                    ->numeric(),
                Forms\Components\TextInput::make('telefon')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('website')
                    ->maxLength(255),
                Forms\Components\TextInput::make('termin_url')
                    ->maxLength(255),
                Forms\Components\TextInput::make('oeffnungszeiten'),
                Forms\Components\TextInput::make('quelle')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('last_imported_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('traeger')
                    ->searchable(),
                Tables\Columns\TextColumn::make('strasse')
                    ->searchable(),
                Tables\Columns\TextColumn::make('plz')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ort')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bundesland.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lat')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lng')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('telefon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('website')
                    ->searchable(),
                Tables\Columns\TextColumn::make('termin_url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('quelle')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_imported_at')
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
            'index' => Pages\ListZulassungsstelles::route('/'),
            'create' => Pages\CreateZulassungsstelle::route('/create'),
            'edit' => Pages\EditZulassungsstelle::route('/{record}/edit'),
        ];
    }
}
