<?php

namespace App\Filament\Resources\AssetIncomings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
//test

class AssetIncomingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('asset.name')
                    ->label('Назва майна')
                    ->wrap()
                    ->lineClamp(4)
                    ->width('250px')
                    ->description(fn ($record) => $record->asset?->inventory_number)
                    ->searchable(),
                TextColumn::make('incoming_type')
                    ->label('Тип надходження')
                    ->badge(),
                TextColumn::make('source')
                    ->label('Джерело надходження')
                    ->searchable(),
                TextColumn::make('document_number')
                    ->label('Номер документу')
                    ->searchable(),
                TextColumn::make('received_at')
                    ->label('Розпочато отримання')
                    ->date()
                    ->description(fn ($record) => $record->completed_at)
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label('Отримано')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                // EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
