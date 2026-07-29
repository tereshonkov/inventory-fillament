<?php

namespace App\Filament\Resources\Employees\RelationManagers;

use App\Enums\AssetStatus;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
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

    protected static ?string $title = 'Майно у користуванні';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Назва')
                    ->required(),
                TextInput::make('inventory_number')
                    ->label('Інвентарний номер'),
                TextInput::make('serial_number')
                    ->label('Серійний номер'),
                Textarea::make('notes')
                    ->label('Нотатки')
                    ->columnSpanFull(),
                TextInput::make('year')
                    ->label('Рік')
                    ->numeric(),
                Select::make('location_id')
                    ->label('Місцезнаходження')
                    ->relationship('location', 'name'),
                Select::make('type_id')
                    ->label('Тип активу')
                    ->relationship('type', 'name'),
                Select::make('custodian_id')
                    ->label('МВО')
                    ->relationship('custodian', 'full_name')
                    ->required(),
                Select::make('status')
                    ->label('Статус')
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
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        return $state;
                    })
                    ->label('Назва')
                    ->searchable(),
                TextColumn::make('inventory_number')
                    ->label('Інвентарний номер')
                    ->searchable(),
                TextColumn::make('serial_number')
                    ->label('Серійний номер')
                    ->searchable(),
                TextColumn::make('year')
                    ->label('Рік')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('location.name')
                    ->label('Місцезнаходження')
                    ->searchable(),
                TextColumn::make('type.name')
                    ->label('Тип активу')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('custodian.full_name')
                    ->searchable()
                    ->label('МВО')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Статус')
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
                DissociateAction::make()
                    ->label('Відкріпити'),
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
