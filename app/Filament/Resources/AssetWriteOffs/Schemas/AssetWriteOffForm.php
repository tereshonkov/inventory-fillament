<?php

namespace App\Filament\Resources\AssetWriteOffs\Schemas;

use App\Enums\AssetStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetWriteOffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Обєкт списання')
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
                        Textarea::make('reason')
                            ->label('Причина')
                            ->columnSpanFull(),
                        TextInput::make('document_number')
                            ->label('Номер акту або іншого документу'),
                    ]),

                Section::make('Активні дії')
                    ->columns(1)
                    ->schema([
                        DatePicker::make('written_off_at')
                            ->label('Почати списання')
                            ->required(),
                        DatePicker::make('completed_at')
                            ->label('Завершити списання'),
                        Textarea::make('notes')
                            ->label('Нотатки')
                            ->columnSpanFull(),
                    ])
            ]);
    }
}
