<?php

namespace App\Filament\Resources\Assets\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

class AssetInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                // One card with two zones (equal height by construction): current state and identifier
                // on the left (the name is already the page heading), compact facts on the right.
                Section::make()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        Group::make([
                            TextEntry::make('inventory_number')
                                ->label('Інвентарний номер')
                                ->fontFamily(FontFamily::Mono)
                                ->copyable()
                                ->copyMessage('Інвентарний номер скопійовано'),
                            TextEntry::make('status')
                                ->hiddenLabel()
                                ->badge()
                                ->size(TextSize::Large),
                        ]),
                        Group::make([
                            TextEntry::make('location.name')
                                ->label('Локація')
                                ->inlineLabel()
                                ->icon(Heroicon::MapPin)
                                ->placeholder('Не призначено'),
                            TextEntry::make('type.name')
                                ->label('Тип')
                                ->inlineLabel()
                                ->placeholder('Не призначено'),
                            TextEntry::make('year')
                                ->label('Рік')
                                ->inlineLabel()
                                ->placeholder('Не вказано'),
                        ]),
                    ]),

                Section::make('Відповідальність та ідентифікація')
                    ->inlineLabel()
                    ->columns(['default' => 1, 'lg' => 2])
                    ->schema([
                        TextEntry::make('custodian.full_name')
                            ->label('МВО')
                            ->placeholder('Не призначено'),
                        TextEntry::make('holder.full_name')
                            ->label('Користувач')
                            ->placeholder('Не призначено'),
                        TextEntry::make('serial_number')
                            ->label('Серійний номер')
                            ->fontFamily(FontFamily::Mono)
                            ->copyable()
                            ->copyMessage('Серійний номер скопійовано')
                            ->placeholder('Не вказано'),
                        TextEntry::make('notes')
                            ->label('Примітки')
                            ->placeholder('Немає приміток')
                            ->columnSpanFull(),
                    ]),

                Section::make('Службова інформація')
                    ->inlineLabel()
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Створено')
                            ->since()
                            ->dateTimeTooltip(),
                        TextEntry::make('updated_at')
                            ->label('Оновлено')
                            ->since()
                            ->dateTimeTooltip(),
                    ]),
            ]);
    }
}
