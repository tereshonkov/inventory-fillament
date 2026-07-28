<?php

namespace App\Filament\Resources\Assets\Tables;

use App\Enums\AssetStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class AssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Назва')
                    ->searchable(),
                TextColumn::make('inventory_number')
                    ->label('Інвентарний номер')
                    ->searchable(),
                TextColumn::make('serial_number')
                    ->label('Серійний номер')
                    ->searchable(),
                TextColumn::make('year')
                    ->label('Рік надходження')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('location.name')
                    ->label('Місцезнаходження')
                    ->searchable(),
                TextColumn::make('type.name')
                    ->label('Тип майна')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('custodian.full_name')
                    ->label('МВО')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('holder.full_name')
                    ->label('Користувач')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge(),
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
                SelectFilter::make('status')->label('Статус')->options(AssetStatus::class),
                SelectFilter::make('location')->label('Місцезнаходження')->relationship('location', 'name'),
                SelectFilter::make('type')->label('Тип майна')->relationship('type', 'name'),
                SelectFilter::make('holder')->label('Користувач')->relationship('holder', 'full_name'),
            ])
            ->recordActions([
                // ViewAction::make(),
                // EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
