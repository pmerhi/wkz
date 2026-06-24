<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuizScoreResource\Pages;
use App\Models\QuizScore;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QuizScoreResource extends Resource
{
    protected static ?string $model = QuizScore::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static ?string $navigationLabel = 'Quiz-Highscores';
    protected static ?string $modelLabel = 'Highscore';
    protected static ?string $pluralModelLabel = 'Quiz-Highscores';
    protected static ?string $navigationGroup = 'Spiel';

    public static function form(Form $form): Form
    {
        // Bearbeiten v. a. zum Anonymisieren/Korrigieren anstößiger Namen.
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(40),
            Forms\Components\TextInput::make('score')->numeric()->minValue(0)->required(),
            Forms\Components\TextInput::make('richtige')->numeric()->minValue(0)->label('Richtige Antworten'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('score')->sortable()->badge()->color('success'),
                Tables\Columns\TextColumn::make('richtige')->label('Richtig')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Datum')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('heute')
                    ->query(fn ($q) => $q->whereDate('created_at', today()))->label('Nur heute'),
            ])
            ->actions([
                Tables\Actions\Action::make('anonymisieren')->icon('heroicon-o-eye-slash')->color('gray')
                    ->requiresConfirmation()
                    ->action(fn (QuizScore $r) => $r->update(['name' => 'Anonym'])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListQuizScores::route('/'),
            'edit'  => Pages\EditQuizScore::route('/{record}/edit'),
        ];
    }
}
