<?php

namespace App\Filament\Resources\Employees\RelationManagers;

use App\Enums\AssetStatus;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssetsHolderRelationManager extends RelationManager
{
    protected static string $relationship = 'assetsHolder';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('inventory_number'),
                TextInput::make('serial_number'),
                Textarea::make('notes')
                    ->columnSpanFull(),
                TextInput::make('year')
                    ->numeric(),
                Select::make('location_id')
                    ->relationship('location', 'name'),
                Select::make('type_id')
                    ->relationship('type', 'name'),
                Select::make('custodian_id')
                    ->relationship('custodian', 'full_name')
                    ->required(),
                Select::make('status')
                    ->options(AssetStatus::class)
                    ->default('balance')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('inventory_number')
                    ->searchable(),
                TextColumn::make('serial_number')
                    ->searchable(),
                TextColumn::make('year')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('location.name')
                    ->searchable(),
                TextColumn::make('type.name')
                    ->searchable(),
                TextColumn::make('custodian.full_name')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // CreateAction::make(),
                AssociateAction::make()
                    ->recordSelectSearchColumns(['name', 'inventory_number', 'serial_number'])
                    ->recordSelect(
                        fn(Select $select) => $select->modifyQueryUsing(
                            fn($query) => $query->where('status', AssetStatus::BALANCE->value)
                        )
                    ),
            ])
            ->recordActions([
                // EditAction::make(),
                DissociateAction::make(),
                // DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    // DeleteBulkAction::make(),
                ]),
            ]);
    }
}
