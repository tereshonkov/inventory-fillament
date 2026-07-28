<?php

namespace App\Filament\Resources\Assets\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основна інформація')
                    ->columns(1)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Назва'),
                        TextEntry::make('inventory_number')
                            ->label('Інвентарний номер')
                            ->placeholder('-'),
                        TextEntry::make('serial_number')
                            ->label('Серійний номер')
                            ->placeholder('-'),
                        TextEntry::make('year')
                            ->label('Рік')
                            ->numeric()
                            ->placeholder('-'),
                    ]),

                Section::make('Розміщення та відповідальність')
                    ->columns(1)
                    ->schema([
                        TextEntry::make('location.name')
                            ->label('Локація')
                            ->placeholder('-'),
                        TextEntry::make('type.name')
                            ->label('Тип')
                            ->placeholder('-'),
                        TextEntry::make('custodian.full_name')
                            ->label('МВО')
                            ->placeholder('-'),
                        TextEntry::make('holder.full_name')
                            ->label('Держатель')
                            ->placeholder('-'),
                    ]),


                Section::make('Статус та примітки')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('status')
                            ->label('Статус')
                            ->badge(),
                        TextEntry::make('notes')
                            ->label('Примітки')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->label('Створено')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label('Оновлено')
                            ->dateTime()
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
