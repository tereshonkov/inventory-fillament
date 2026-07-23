<?php

namespace App\Filament\Resources\AssetIncomings\Schemas;

use App\Enums\AssetStatus;
use App\Enums\IncomingType;
use App\Enums\UserRole;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AssetIncomingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('asset_id')
                    ->relationship('asset', 'name')
                    ->required()
                    ->createOptionForm([
                        TextInput::make('name')->required(),
                        TextInput::make('notes'),
                        TextInput::make('year')->numeric(),
                        Select::make('type_id')->relationship('type', 'name'),
                        Select::make('status')->options(AssetStatus::class)->default('capitalize'),
                        Select::make('custodian_id')
                            ->relationship('custodian', 'full_name')
                            ->default(fn() => auth()->user()->role === UserRole::EDITOR ? auth()->user()->employee_id : null)
                            ->required(),
                    ]),
                Select::make('incoming_type')
                    ->options(IncomingType::class)
                    ->default('new')
                    ->required(),
                TextInput::make('source'),
                TextInput::make('document_number'),
                DatePicker::make('received_at')
                    ->required(),
                DatePicker::make('completed_at'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
