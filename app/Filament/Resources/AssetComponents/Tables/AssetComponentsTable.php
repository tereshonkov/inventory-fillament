<?php

namespace App\Filament\Resources\AssetComponents\Tables;

use App\Enums\ComponentKind;
use App\Filament\Resources\Assets\AssetResource;
use App\Models\AssetComponent;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssetComponentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['asset.location', 'asset.holder', 'location', 'holder']))
            ->defaultSort('id', 'desc')
            ->paginationPageOptions([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->emptyStateIcon('heroicon-o-computer-desktop')
            ->emptyStateHeading('Комплектуючих ще не додано')
            ->emptyStateDescription('Комплектуючі додаються на картці робочої станції.')
            ->columns([
                TextColumn::make('kind')
                    ->label('Вид')
                    ->badge()
                    ->sortable(),
                TextColumn::make('serial_number')
                    ->label('Серійний номер')
                    ->fontFamily(FontFamily::Mono)
                    ->copyable()
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Опис')
                    ->limit(60)
                    ->tooltip(fn (TextColumn $column): ?string => $column->getState())
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('holder.full_name')
                    ->label('У кого')
                    ->state(fn (AssetComponent $record): ?string => $record->effectiveHolderName())
                    ->description(fn (AssetComponent $record): ?string => $record->effectiveHolderName() && $record->usesStationHolder() ? 'як у станції' : null)
                    ->placeholder('—'),
                TextColumn::make('location.name')
                    ->label('Локація')
                    ->state(fn (AssetComponent $record): ?string => $record->effectiveLocationName())
                    ->description(fn (AssetComponent $record): ?string => $record->effectiveLocationName() && $record->usesStationLocation() ? 'як у станції' : null)
                    ->placeholder('—'),
                IconColumn::make('is_working')
                    ->label('Справна')
                    ->boolean(),
                TextColumn::make('asset.inventory_number')
                    ->label('Інв. № комплекту')
                    ->fontFamily(FontFamily::Mono)
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('asset.name')
                    ->label('Станція')
                    ->state(fn (AssetComponent $record): string => $record->asset->short_name)
                    ->tooltip(fn (AssetComponent $record): string => $record->asset->name),
            ])
            ->filters([
                SelectFilter::make('kind')
                    ->label('Вид')
                    ->options(ComponentKind::class),
            ])
            ->recordUrl(fn (AssetComponent $record): string => AssetResource::getUrl('view', ['record' => $record->asset_id]))
            ->recordActions([])
            ->toolbarActions([]);
    }
}
