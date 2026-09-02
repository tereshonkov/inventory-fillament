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
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('asset.name')
                    ->label('Назва')
                    ->wrap()
                    ->lineClamp(4)
                    ->width('450px')
                    ->description(fn($record) => $record->asset?->inventory_number, position: 'above')
                    ->description(fn($record) => $record->asset?->serial_number, position: 'below')
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        return $state;
                    })
                    ->searchable(),
                TextColumn::make('department.name')
                    ->label('Підрозділ')
                    ->searchable(),
                TextColumn::make('document_number')
                    ->label('Номер документу')
                    ->searchable(),
                TextColumn::make('transferred_at')
                    ->label('Розпочату передачу')
                    ->description(fn($record) => $record->completed_at?->format('d.m.Y'))
                    ->date()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label('Передано')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true)
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
