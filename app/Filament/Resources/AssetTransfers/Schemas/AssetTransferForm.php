<?php

namespace App\Filament\Resources\AssetTransfers\Schemas;

use App\Enums\AssetStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Обєкт що передається')
                    ->columns(1)
                    ->schema([
                        Select::make('asset_id')
                            ->label('Майно')
                            ->relationship(
                                'asset',
                                'name',
                                modifyQueryUsing: fn($query, string $operation) => $operation === 'create'
                                    ? $query->where('status', AssetStatus::BALANCE->value)
                                    : $query,
                            )
                            ->searchable(['name', 'inventory_number', 'serial_number'])
                            ->required(),
                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->label('Кому передаємо')
                            ->preload()
                            ->required(),
                        TextInput::make('document_number')
                            ->label('Номер документу про переміщення'),
                    ]),

                Section::make('Активні дії')
                    ->columns(1)
                    ->schema([
                        DatePicker::make('transferred_at')
                            ->label('Почати передачу')
                            ->required(),
                        DatePicker::make('completed_at')
                            ->label('Передачу завершено'),
                        Textarea::make('notes')
                            ->label('Нотатки')
                            ->columnSpanFull(),
                    ])
            ]);
    }
}
