<?php

namespace App\Filament\Resources\AssetComponents;

use App\Filament\Resources\AssetComponents\Pages\ListAssetComponents;
use App\Filament\Resources\AssetComponents\Tables\AssetComponentsTable;
use App\Models\AssetComponent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Загальний список усіх комплектуючих (включно з тими, що лежать на складі). Лише перегляд і пошук:
 * додаються та редагуються комплектуючі на картці робочої станції.
 */
class AssetComponentResource extends Resource
{
    protected static ?string $model = AssetComponent::class;

    protected static ?string $modelLabel = 'Комплектуюча';

    protected static ?string $pluralModelLabel = 'Комплектуючі';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static string|UnitEnum|null $navigationGroup = 'Облік майна';

    protected static ?string $navigationLabel = 'Комплектуючі';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'serial_number';

    public static function table(Table $table): Table
    {
        return AssetComponentsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssetComponents::route('/'),
        ];
    }
}
