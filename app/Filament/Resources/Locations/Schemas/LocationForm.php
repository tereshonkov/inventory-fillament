<?php

namespace App\Filament\Resources\Locations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Назва локації')
                    ->placeholder('Наприклад: ХМУ')
                    ->required()
                    ->unique(ignoreRecord: true),
            ]);
    }
}
