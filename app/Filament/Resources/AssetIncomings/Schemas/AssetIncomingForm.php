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
                Section::make('Інформація про отримання')
                    ->schema([
                        Select::make('incoming_type')
                            ->label('Тип надходження')
                            ->options(IncomingType::class)
                            ->default('new')
                            ->required(),
                        TextInput::make('source')
                            ->label('Джерело надходження'),
                        TextInput::make('document_number')
                            ->label('Номер документу'),
                        DatePicker::make('received_at')
                            ->label('Розпочато отримання')
                            ->required(),
                        DatePicker::make('completed_at')
                            ->label('Отримано'),
                        Textarea::make('notes')
                            ->label('Нотатки')
                            ->columnSpanFull(),
                    ]),
                Section::make('Заповнення майна')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Назва')
                            ->required(),
                        TextInput::make('inventory_number')
                            ->label('Інвентарний номер'),
                        TextInput::make('serial_number')
                            ->label('Серійний номер'),
                        Textarea::make('asset_notes')
                            ->label('Нотатки')
                            ->columnSpanFull(),
                        TextInput::make('year')
                            ->label('Рік')
                            ->numeric(),
                        Select::make('location_id')
                            ->label('Місцезнаходження')
                            ->options(fn() => Location::pluck('name', 'id')),
                        Select::make('type_id')
                            ->label('Тип майна')
                            ->options(fn() => AssetType::pluck('name', 'id')),
                        Select::make('custodian_id')
                            ->label('МВО')
                            ->options(fn() => Employee::pluck('full_name', 'id'))
                            ->default(fn() => auth()->user()->role === UserRole::EDITOR ? auth()->user()->employee_id : null)
                            ->required(),
                        Select::make('holder_id')
                            ->label('Користувач')
                            ->options(fn() => Employee::pluck('full_name', 'id')),
                        Hidden::make('status')->default('capitalize'),
                    ])
            ]);
    }
}
