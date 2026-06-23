<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExtraktZulassungsstelleResource\Pages;
use App\Filament\Resources\ExtraktZulassungsstelleResource\RelationManagers;
use App\Models\ExtraktZulassungsstelle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExtraktZulassungsstelleResource extends Resource
{
    protected static ?string $model = ExtraktZulassungsstelle::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';
    protected static ?string $navigationGroup = 'Markt';
    protected static ?string $navigationLabel = 'Extrakt: Zulassungsstellen';
    protected static ?string $modelLabel = 'Extrakt-Stelle';
    protected static ?string $pluralModelLabel = 'Extrakt: Zulassungsstellen';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('wettbewerber_id')
                    ->relationship('wettbewerber', 'name')
                    ->required(),
                Forms\Components\TextInput::make('crawl_seite_id')
                    ->numeric(),
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
                Forms\Components\TextInput::make('termin_url')
                    ->maxLength(255),
                Forms\Components\TextInput::make('oeffnungszeiten'),
                Forms\Components\Select::make('gemeinde_id')
                    ->relationship('gemeinde', 'name'),
                Forms\Components\Select::make('kreis_id')
                    ->relationship('kreis', 'name'),
                Forms\Components\TextInput::make('quelle_url')
                    ->maxLength(255),
                Forms\Components\TextInput::make('roh'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('wettbewerber.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('crawl_seite_id')
                    ->numeric()
                    ->sortable(),
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
                Tables\Columns\TextColumn::make('termin_url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gemeinde.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kreis.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quelle_url')
                    ->searchable(),
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
                Tables\Filters\SelectFilter::make('wettbewerber')->relationship('wettbewerber', 'name'),
                Tables\Filters\TernaryFilter::make('gemeinde_id')->label('AGS-gematcht')
                    ->nullable()->trueLabel('mit AGS')->falseLabel('ohne AGS')
                    ->queries(
                        true: fn ($q) => $q->whereNotNull('gemeinde_id'),
                        false: fn ($q) => $q->whereNull('gemeinde_id'),
                        blank: fn ($q) => $q,
                    ),
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
            'index' => Pages\ListExtraktZulassungsstelles::route('/'),
            'create' => Pages\CreateExtraktZulassungsstelle::route('/create'),
            'edit' => Pages\EditExtraktZulassungsstelle::route('/{record}/edit'),
        ];
    }
}
