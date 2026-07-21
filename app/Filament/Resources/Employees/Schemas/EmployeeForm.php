<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Enums\EmployeeStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('full_name')
                    ->required(),
                TextInput::make('position'),
                TextInput::make('phone')
                    ->tel(),
                Select::make('status')
                    ->options(EmployeeStatus::class)
                    ->default('active')
                    ->required(),
            ]);
    }
}
