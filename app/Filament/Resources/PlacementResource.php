<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlacementResource\Pages;
use App\Models\Placement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlacementResource extends Resource
{
    protected static ?string $model = Placement::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Werbung';
    protected static ?string $navigationLabel = 'Platzierungen';
    protected static ?string $modelLabel = 'Platzierung';
    protected static ?string $pluralModelLabel = 'Platzierungen';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('partner_id')
                    ->label('Partner')
                    ->relationship('partner', 'name')
                    ->searchable()->preload()->required(),
                Forms\Components\TextInput::make('name')
                    ->label('Bezeichnung')
                    ->required()->maxLength(255),
                Forms\Components\Select::make('typ')
                    ->options(['block' => 'Werbeblock', 'in_text' => 'In-Text-Link'])
                    ->default('block')->required(),
                Forms\Components\TextInput::make('position')
                    ->label('Position / Slot')
                    ->helperText('Slot-Name, an dem die Platzierung erscheint, z.B. "sidebar", "ratgeber_unten".')
                    ->maxLength(255),
                Forms\Components\TextInput::make('ziel_url')
                    ->label('Ziel-URL')
                    ->url()->required()->maxLength(255)
                    ->helperText('Affiliate-Ziel. Aufruf läuft getrackt über /go/{id}.'),
                Forms\Components\Toggle::make('aktiv')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Bezeichnung')->searchable(),
                Tables\Columns\TextColumn::make('partner.name')->label('Partner')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('typ')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'in_text' ? 'In-Text-Link' : 'Werbeblock'),
                Tables\Columns\TextColumn::make('position')->label('Slot')->searchable(),
                Tables\Columns\TextColumn::make('clicks_count')->label('Klicks')->counts('clicks')->sortable(),
                Tables\Columns\TextColumn::make('tracking_url')
                    ->label('Tracking-URL')
                    ->state(fn (Placement $record) => url('/go/'.$record->id))
                    ->copyable()->copyMessage('Kopiert')->toggleable(),
                Tables\Columns\IconColumn::make('aktiv')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('typ')
                    ->options(['block' => 'Werbeblock', 'in_text' => 'In-Text-Link']),
                Tables\Filters\TernaryFilter::make('aktiv'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('clicks_count', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('clicks');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlacements::route('/'),
            'create' => Pages\CreatePlacement::route('/create'),
            'edit' => Pages\EditPlacement::route('/{record}/edit'),
        ];
    }
}
