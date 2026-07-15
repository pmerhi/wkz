<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LinkCheckResource\Pages;
use App\Models\LinkCheck;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LinkCheckResource extends Resource
{
    protected static ?string $model = LinkCheck::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationLabel = 'Link-Check';
    protected static ?string $modelLabel = 'Externer Link';
    protected static ?string $pluralModelLabel = 'Link-Check';
    protected static ?string $navigationGroup = 'Inhalte';

    /** Anzahl auffälliger Links als Badge am Menüpunkt. */
    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('ok', false)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('ok', 'asc')   // defekte zuerst
            ->columns([
                Tables\Columns\IconColumn::make('ok')->label('')->boolean()
                    ->trueIcon('heroicon-o-check-circle')->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('status')->label('HTTP')->badge()
                    ->color(fn ($state, LinkCheck $r) => $r->ok ? 'success' : ($state && $state < 500 ? 'warning' : 'danger'))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('url')->searchable()->wrap()->limit(80)
                    ->url(fn (LinkCheck $r) => $r->url, true)->color('primary'),
                Tables\Columns\TextColumn::make('fehler')->label('Hinweis')->wrap()->limit(60)->toggleable(),
                Tables\Columns\TextColumn::make('quellen')->label('Fundstellen')->wrap()->limit(60)
                    ->tooltip(fn (LinkCheck $r) => $r->quellen)->toggleable(),
                Tables\Columns\TextColumn::make('geprueft_at')->label('Geprüft')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('ok')->label('Status')
                    ->trueLabel('nur OK')->falseLabel('nur auffällige')->placeholder('alle'),
            ])
            ->actions([
                Tables\Actions\Action::make('oeffnen')->label('Öffnen')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (LinkCheck $r) => $r->url, true),
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
            'index' => Pages\ListLinkChecks::route('/'),
        ];
    }
}
