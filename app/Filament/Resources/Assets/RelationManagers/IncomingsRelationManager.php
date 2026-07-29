<?php

namespace App\Filament\Resources\Assets\RelationManagers;

use App\Enums\IncomingType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IncomingsRelationManager extends RelationManager
{
    protected static string $relationship = 'incomings';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('incoming_type')
                    ->options(IncomingType::class)
                    ->label('Тип отримання')
                    ->default('new')
                    ->required(),
                TextInput::make('source')
                    ->label('Джерело отримання'),
                TextInput::make('document_number')
                    ->label('Документ отримання'),
                DatePicker::make('received_at')
                    ->label('Розпочати отримання')
                    ->required(),
                DatePicker::make('completed_at')
                    ->label('Отримано'),
                Textarea::make('notes')
                    ->label('Нотатки')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('document_number')
            ->columns([
                TextColumn::make('incoming_type')
                    ->label('Тип отримання')
                    ->badge(),
                TextColumn::make('source')
                    ->label('Джерело отримання'),
                // ->searchable(),
                TextColumn::make('document_number')
                    ->label('Документ отримання'),
                // ->searchable(),
                TextColumn::make('received_at')
                    ->label('Розпочати отримання')
                    ->date()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label('Отримано')
                    ->date()
                    ->sortable(),
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
                // AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                // DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DissociateBulkAction::make(),
                    // DeleteBulkAction::make(),
                ]),
            ]);
    }
}
