<?php

namespace App\Filament\Resources\AssetWriteOffs;

use App\Enums\UserRole;
use App\Filament\Resources\AssetWriteOffs\Pages\CreateAssetWriteOff;
use App\Filament\Resources\AssetWriteOffs\Pages\EditAssetWriteOff;
use App\Filament\Resources\AssetWriteOffs\Pages\ListAssetWriteOffs;
use App\Filament\Resources\AssetWriteOffs\Schemas\AssetWriteOffForm;
use App\Filament\Resources\AssetWriteOffs\Tables\AssetWriteOffsTable;
use App\Models\AssetWriteOff;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssetWriteOffResource extends Resource
{
    protected static ?string $model = AssetWriteOff::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'document_number';

    public static function form(Schema $schema): Schema
    {
        return AssetWriteOffForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetWriteOffsTable::configure($table);
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
            'index' => ListAssetWriteOffs::route('/'),
            'create' => CreateAssetWriteOff::route('/create'),
            'edit' => EditAssetWriteOff::route('/{record}/edit'),
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
