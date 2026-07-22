<?php

namespace App\Filament\Resources\Assets\Schemas;

use App\Enums\AssetStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('inventory_number'),
                TextInput::make('serial_number'),
                Textarea::make('notes')
                    ->columnSpanFull(),
                TextInput::make('year')
                    ->numeric(),
                Select::make('location_id')
                    ->relationship('location', 'name'),
                Select::make('type_id')
                    ->relationship('type', 'name'),
                Select::make('custodian_id')
                    ->relationship('custodian', 'id')
                    ->required(),
                Select::make('holder_id')
                    ->relationship('holder', 'id'),
                Select::make('status')
                    ->options(AssetStatus::class)
                    ->default('balance')
                    ->required(),
            ]);
    }
}
