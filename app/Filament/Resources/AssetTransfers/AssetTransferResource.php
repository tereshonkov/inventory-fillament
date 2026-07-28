<?php

namespace App\Filament\Resources\AssetTransfers;

use App\Enums\UserRole;
use App\Filament\Resources\AssetTransfers\Pages\CreateAssetTransfer;
use App\Filament\Resources\AssetTransfers\Pages\EditAssetTransfer;
use App\Filament\Resources\AssetTransfers\Pages\ListAssetTransfers;
use App\Filament\Resources\AssetTransfers\Schemas\AssetTransferForm;
use App\Filament\Resources\AssetTransfers\Tables\AssetTransfersTable;
use App\Models\AssetTransfer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AssetTransferResource extends Resource
{
    protected static ?string $model = AssetTransfer::class;

    protected static ?string $modelLabel = 'Передача активу';

    protected static ?string $pluralModelLabel = 'Передача активів';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'Облік майна';

    protected static ?string $navigationLabel = 'Передачі';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'document_number';

    public static function form(Schema $schema): Schema
    {
        return AssetTransferForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetTransfersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssetTransfers::route('/'),
            'create' => CreateAssetTransfer::route('/create'),
            'edit' => EditAssetTransfer::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery();

        return match ($user->role) {
            UserRole::EDITOR => $query->whereHas('asset', fn($q) => $q->where('custodian_id', $user->employee_id)),
            default => $query,
        };
    }
}
