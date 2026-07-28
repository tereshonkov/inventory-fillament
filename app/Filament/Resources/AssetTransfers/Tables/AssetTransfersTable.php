<?php

namespace App\Filament\Resources\AssetTransfers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssetTransfersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('asset.name')
                    ->label('Назва')
                    ->searchable(),
                TextColumn::make('department.name')
                    ->label('Підрозділ')
                    ->searchable(),
                TextColumn::make('document_number')
                    ->label('Номер документу')
                    ->searchable(),
                TextColumn::make('transferred_at')
                    ->label('Розпочату передачу')
                    ->date()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label('Передано')
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
