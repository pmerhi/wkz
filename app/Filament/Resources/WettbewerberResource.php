<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WettbewerberResource\Pages;
use App\Filament\Resources\WettbewerberResource\RelationManagers;
use App\Models\Wettbewerber;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WettbewerberResource extends Resource
{
    protected static ?string $model = Wettbewerber::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'Markt';
    protected static ?string $navigationLabel = 'Wettbewerber';
    protected static ?string $modelLabel = 'Wettbewerber';
    protected static ?string $pluralModelLabel = 'Wettbewerber';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('domain')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('url')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('typ')
                    ->maxLength(255),
                Forms\Components\TextInput::make('betreiber')
                    ->maxLength(255),
                Forms\Components\TextInput::make('rang')
                    ->numeric(),
                Forms\Components\Textarea::make('dedup_hinweis')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('notizen')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rang')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('domain')
                    ->url(fn ($record) => $record->url)->openUrlInNewTab()
                    ->color('primary')->searchable(),
                Tables\Columns\TextColumn::make('typ')->badge()->sortable(),
                Tables\Columns\TextColumn::make('betreiber')->wrap()->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('dedup_hinweis')->label('Dedup/Notiz')
                    ->wrap()->limit(120)->toggleable(),
            ])
            ->defaultSort('rang')
            ->filters([
                Tables\Filters\SelectFilter::make('typ')
                    ->options(['funnel' => 'Funnel', 'shop' => 'Shop', 'mix' => 'Mix', 'verzeichnis' => 'Verzeichnis', 'ratgeber' => 'Ratgeber']),
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
            'index' => Pages\ListWettbewerbers::route('/'),
            'create' => Pages\CreateWettbewerber::route('/create'),
            'edit' => Pages\EditWettbewerber::route('/{record}/edit'),
        ];
    }
}
