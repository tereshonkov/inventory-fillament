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

class AssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Назва')
                    ->width('400px')
                    ->wrap()
                    ->lineClamp(4)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        return $state;
                    })
                    ->searchable(),
                TextColumn::make('inventory_number')
                    ->label('Інвентарний номер')
                    ->searchable(),
                TextColumn::make('serial_number')
                    ->label('Серійний номер')
                    ->width('250px')
                    ->wrap()
                    ->lineClamp(2)
                    ->searchable(),
                TextColumn::make('year')
                    ->label('Рік надходження')
                    // ->toggleable(isToggledHiddenByDefault: true)
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
                SelectFilter::make('status')->label('Статус')->options(AssetStatus::class)->multiple(),
                SelectFilter::make('location')->label('Місцезнаходження')->relationship('location', 'name')->multiple(),
                SelectFilter::make('type')->label('Тип майна')->relationship('type', 'name')->multiple(),
                SelectFilter::make('holder')->label('Користувач')->relationship('holder', 'full_name')->multiple(),
                SelectFilter::make('year')
                    ->label('Рік надходження')
                    ->options(fn() => Asset::query()->whereNotNull('year')->distinct()->orderByDesc('year')->pluck('year', 'year'))
                    ->multiple(),
            ])
            ->recordActions([
                // ViewAction::make(),
                // EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Експортувати')
                    ->exporter(AssetExporter::class)
                    ->formats([ExportFormat::Xlsx]),
                ImportAction::make()
                    ->label('Імпортувати')
                    ->importer(AssetImporter::class)
                    ->visible(fn() => auth()->user()->role === UserRole::ADMIN),
            ]);
    }
}
