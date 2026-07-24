<?php

namespace App\Filament\Resources\AssetTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AssetTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
            ]);
    }
}
