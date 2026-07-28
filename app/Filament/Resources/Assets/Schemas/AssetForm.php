<?php

namespace App\Filament\Resources\Assets\Schemas;

use App\Enums\AssetStatus;
use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основна інформація')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('inventory_number'),
                        TextInput::make('serial_number'),
                        TextInput::make('year')
                            ->numeric(),
                    ]),

                Section::make('Розміщення та відповідальність')
                    ->columns(2)
                    ->schema([
                        Select::make('location_id')
                            ->relationship('location', 'name'),
                        Select::make('type_id')
                            ->relationship('type', 'name'),
                        Select::make('custodian_id')
                            ->relationship('custodian', 'full_name')
                            ->required(),
                        Select::make('holder_id')
                            ->relationship('holder', 'full_name'),
                    ]),

                Section::make('Статус та примітки')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->options(function () {
                                if (auth()->user()->role === UserRole::ADMIN) {
                                    return collect(AssetStatus::cases())
                                        ->mapWithKeys(fn(AssetStatus $status) => [$status->value => $status->getLabel()]);
                                }

                                return [
                                    AssetStatus::BALANCE->value => AssetStatus::BALANCE->getLabel(),
                                    AssetStatus::NOT_PUT_IN_TO_OPERATION->value => AssetStatus::NOT_PUT_IN_TO_OPERATION->getLabel(),
                                    AssetStatus::REPAIR->value => AssetStatus::REPAIR->getLabel(),
                                    AssetStatus::LOST->value => AssetStatus::LOST->getLabel(),
                                ];
                            })
                            ->required(),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
