<?php

namespace App\Filament\Resources\AssetIncomings;

use App\Enums\UserRole;
use App\Filament\Resources\AssetIncomings\Pages\CreateAssetIncoming;
use App\Filament\Resources\AssetIncomings\Pages\EditAssetIncoming;
use App\Filament\Resources\AssetIncomings\Pages\ListAssetIncomings;
use App\Filament\Resources\AssetIncomings\Schemas\AssetIncomingForm;
use App\Filament\Resources\AssetIncomings\Tables\AssetIncomingsTable;
use App\Models\AssetIncoming;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AssetIncomingResource extends Resource
{
    protected static ?string $model = AssetIncoming::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = 'Облік майна';

    protected static ?string $navigationLabel = 'Надходження';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'document_number';

    public static function form(Schema $schema): Schema
    {
        return AssetIncomingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetIncomingsTable::configure($table);
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
            'index' => ListAssetIncomings::route('/'),
            'create' => CreateAssetIncoming::route('/create'),
            'edit' => EditAssetIncoming::route('/{record}/edit'),
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
