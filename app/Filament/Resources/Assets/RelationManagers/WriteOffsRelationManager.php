<?php

namespace App\Filament\Resources\Assets\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WriteOffsRelationManager extends RelationManager
{
    protected static string $relationship = 'writeOffs';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('reason')
                    ->label('Причина списання')
                    ->columnSpanFull(),
                TextInput::make('document_number')
                    ->label('Номер документа'),
                DatePicker::make('written_off_at')
                    ->label('Розпочато списання')
                    ->required(),
                DatePicker::make('completed_at')
                    ->label('Списано'),
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
                TextColumn::make('document_number')
                    ->label('Номер документу'),
                // ->searchable(),
                TextColumn::make('written_off_at')
                    ->label('Розпочато списання')
                    ->date()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label('Списано')
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
