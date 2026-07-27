<?php

namespace App\Filament\Resources\AssetTransfers\Schemas;

use App\Enums\AssetStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AssetTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('asset_id')
                    ->relationship('asset', 'name', fn($query) => $query->where('status', AssetStatus::BALANCE->value))
                    ->searchable(['name', 'inventory_number', 'serial_number'])
                    ->required(),
                Select::make('department_id')
                    ->relationship('department', 'name')
                    ->preload()
                    ->required(),
                TextInput::make('document_number'),
                DatePicker::make('transferred_at')
                    ->required(),
                DatePicker::make('completed_at'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
