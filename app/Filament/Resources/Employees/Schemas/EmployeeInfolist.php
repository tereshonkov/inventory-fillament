<?php

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Загальна інформація')
                    ->columns(1)
                    ->schema([
                        TextEntry::make('full_name')
                            ->label('ПІБ'),
                        TextEntry::make('position')
                            ->label('Посада'),
                        TextEntry::make('phone')
                            ->label('Телефон'),
                    ]),

                Section::make('Технічна інформація')
                    ->columns(1)
                    ->schema([
                        TextEntry::make('status')
                            ->label('Статус')
                            ->badge(),
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Дата створення'),
                        TextEntry::make('updated_at')
                            ->dateTime()
                            ->label('Дата корегування'),
                    ])
            ]);
    }
}
