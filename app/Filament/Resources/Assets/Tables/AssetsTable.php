<?php

namespace App\Filament\Resources\Assets\Tables;

use App\Enums\AssetStatus;
use App\Enums\UserRole;
use App\Filament\Exports\AssetExporter;
use App\Filament\Imports\AssetImporter;
use App\Models\Asset;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\ImportAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Enums\FiltersLayout;

class AssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([5, 10, 25, 50])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('name')
                    ->label('Назва')
                    ->width('600px')
                    ->wrap()
                    ->lineClamp(4)
                    ->tooltip(fn (TextColumn $column): ?string => $column->getState())
                    ->searchable(),
                TextColumn::make('inventory_number')
                    ->label('Інвентарний номер')
                    ->width('250px')
                    ->wrap()
                    ->lineClamp(2)
                    ->searchable()
                    ->description(fn($record) => $record->serial_number),
                TextColumn::make('serial_number')
                    ->label('Серійний номер')
                    ->width('250px')
                    ->wrap()
                    ->lineClamp(2)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('year')
                    ->label('Рік')
                    // ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('location.name')
                    ->label('Місцезнаходження')
                    ->searchable()
                    ->description(fn($record) => $record->holder?->full_name),
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
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->size(TextSize::Medium)
                    ->weight(FontWeight::Bold),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filtersFormWidth('2xl')
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(AssetStatus::class)
                    ->searchable()
                    ->multiple(),
                SelectFilter::make('location')
                    ->label('Місцезнаходження')
                    ->relationship('location', 'name')
                    ->preload()
                    ->searchable()
                    ->multiple(),
                SelectFilter::make('type')
                    ->label('Тип майна')
                    ->relationship('type', 'name')
                    ->preload()
                    ->searchable()
                    ->multiple(),
                SelectFilter::make('holder')
                    ->label('Користувач')
                    ->relationship('holder', 'full_name')
                    ->preload()
                    ->searchable()
                    ->multiple(),
                SelectFilter::make('year')
                    ->label('Рік надходження')
                    ->options(fn() => Asset::query()->whereNotNull('year')->distinct()->orderByDesc('year')->pluck('year', 'year'))
                    ->searchable()
                    ->multiple(),
            ], layout: FiltersLayout::Modal)
            ->recordActions([
                // ViewAction::make(),
                // EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
        // ->headerActions([
        //     ExportAction::make()
        //         ->label('Експортувати')
        //         ->exporter(AssetExporter::class)
        //         ->formats([ExportFormat::Xlsx]),
        //     ImportAction::make()
        //         ->label('Імпортувати')
        //         ->importer(AssetImporter::class)
        //         ->visible(fn() => auth()->user()->role === UserRole::ADMIN),
        // ]);
    }
}
