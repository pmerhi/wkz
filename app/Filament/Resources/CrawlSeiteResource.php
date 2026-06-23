<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CrawlSeiteResource\Pages;
use App\Filament\Resources\CrawlSeiteResource\RelationManagers;
use App\Models\CrawlSeite;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CrawlSeiteResource extends Resource
{
    protected static ?string $model = CrawlSeite::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationGroup = 'Markt';
    protected static ?string $navigationLabel = 'Seiten-Archiv';
    protected static ?string $modelLabel = 'Archiv-Seite';
    protected static ?string $pluralModelLabel = 'Seiten-Archiv';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('wettbewerber_id')
                    ->relationship('wettbewerber', 'name')
                    ->required(),
                Forms\Components\Textarea::make('url')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('url_hash')
                    ->required()
                    ->maxLength(40),
                Forms\Components\TextInput::make('http_status')
                    ->numeric(),
                Forms\Components\TextInput::make('content_type')
                    ->maxLength(255),
                Forms\Components\TextInput::make('titel')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\Textarea::make('html')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('text')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('inhalt_hash')
                    ->maxLength(40),
                Forms\Components\DateTimePicker::make('abgerufen_am'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('wettbewerber.name')->label('Wettbewerber')->sortable(),
                Tables\Columns\TextColumn::make('url')->url(fn ($record) => $record->url)->openUrlInNewTab()
                    ->color('primary')->limit(60)->searchable(),
                Tables\Columns\TextColumn::make('titel')->limit(50)->searchable(),
                Tables\Columns\TextColumn::make('http_status')->label('Status')->sortable(),
                Tables\Columns\TextColumn::make('abgerufen_am')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('wettbewerber')->relationship('wettbewerber', 'name'),
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
            'index' => Pages\ListCrawlSeites::route('/'),
            'create' => Pages\CreateCrawlSeite::route('/create'),
            'edit' => Pages\EditCrawlSeite::route('/{record}/edit'),
        ];
    }
}
