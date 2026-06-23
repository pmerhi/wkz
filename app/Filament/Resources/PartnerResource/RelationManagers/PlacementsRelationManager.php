<?php

namespace App\Filament\Resources\PartnerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PlacementsRelationManager extends RelationManager
{
    protected static string $relationship = 'placements';

    protected static ?string $title = 'Platzierungen';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Bezeichnung')->required()->maxLength(255),
                Forms\Components\Select::make('typ')
                    ->options(['block' => 'Werbeblock', 'in_text' => 'In-Text-Link'])
                    ->default('block')->required(),
                Forms\Components\TextInput::make('position')
                    ->label('Position / Slot')->maxLength(255),
                Forms\Components\TextInput::make('ziel_url')
                    ->label('Ziel-URL')->url()->required()->maxLength(255),
                Forms\Components\Toggle::make('aktiv')->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Bezeichnung'),
                Tables\Columns\TextColumn::make('typ')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'in_text' ? 'In-Text-Link' : 'Werbeblock'),
                Tables\Columns\TextColumn::make('position')->label('Slot'),
                Tables\Columns\TextColumn::make('clicks_count')->label('Klicks')->counts('clicks'),
                Tables\Columns\IconColumn::make('aktiv')->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
