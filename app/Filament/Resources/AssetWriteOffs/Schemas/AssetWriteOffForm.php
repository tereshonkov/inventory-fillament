<?php

namespace App\Filament\Resources\AssetWriteOffs\Schemas;

use App\Enums\AssetStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AssetWriteOffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('asset_id')
                    ->relationship(
                        'asset',
                        'name',
                        modifyQueryUsing: fn($query, string $operation) => $operation === 'create'
                            ? $query->where('status', AssetStatus::BALANCE->value)
                            : $query,
                    )
                    ->searchable(['name', 'inventory_number', 'serial_number'])
                    ->required(),
                Textarea::make('reason')
                    ->columnSpanFull(),
                TextInput::make('document_number'),
                DatePicker::make('written_off_at')
                    ->required(),
                DatePicker::make('completed_at'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
