<?php

namespace App\Filament\Resources\AssetIncomings\Schemas;

use App\Enums\IncomingType;
use App\Enums\UserRole;
use App\Models\AssetType;
use App\Models\Employee;
use App\Models\Location;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetIncomingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Incoming Fileds')
                    ->schema([
                        Select::make('incoming_type')
                            ->options(IncomingType::class)
                            ->default('new')
                            ->required(),
                        TextInput::make('source'),
                        TextInput::make('document_number'),
                        DatePicker::make('received_at')
                            ->required(),
                        DatePicker::make('completed_at'),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),
                Section::make('Asset Fileds')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('inventory_number'),
                        TextInput::make('serial_number'),
                        Textarea::make('asset_notes')
                            ->columnSpanFull(),
                        TextInput::make('year')
                            ->numeric(),
                        Select::make('location_id')
                            ->options(fn() => Location::pluck('name', 'id')),
                        Select::make('type_id')
                            ->options(fn() => AssetType::pluck('name', 'id')),
                        Select::make('custodian_id')
                            ->options(fn() => Employee::pluck('full_name', 'id'))
                            ->default(fn() => auth()->user()->role === UserRole::EDITOR ? auth()->user()->employee_id : null)
                            ->required(),
                        Select::make('holder_id')
                            ->options(fn() => Employee::pluck('full_name', 'id')),
                        Hidden::make('status')->default('capitalize'),
                    ])
            ]);
    }
}
