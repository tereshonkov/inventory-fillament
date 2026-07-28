<?php

namespace App\Filament\Resources\Departments\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Назва підрозділу')
                    ->placeholder('Наприклад: ХРУП №3 ГУНП в Харківській області')
                    ->required(),
            ]);
    }
}
