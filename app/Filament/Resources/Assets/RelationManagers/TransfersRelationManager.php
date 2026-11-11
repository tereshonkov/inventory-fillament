<?php

namespace App\Filament\Resources\Assets\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TransfersRelationManager extends RelationManager
{
    protected static string $relationship = 'transfers';

    protected static ?string $title = 'Передано';

    // Render the table together with the page: a lazy block shows an empty white area until it scrolls into view.
    protected static bool $isLazy = false;

    protected static ?string $modelLabel = 'передача';

    protected static ?string $pluralModelLabel = 'передачі';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->transfers()->count();

        return $count > 0 ? (string) $count : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('department_id')
                    ->label('Підрозділ')
                    ->relationship('department', 'name')
                    ->required(),
                TextInput::make('document_number')
                    ->label('Номер документа'),
                DatePicker::make('transferred_at')
                    ->label('Розпочато передачу')
                    ->required(),
                DatePicker::make('completed_at')
                    ->label('Передано'),
                Textarea::make('notes')
                    ->label('Нотатки')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('')
            ->emptyStateIcon(Heroicon::OutlinedTruck)
            ->emptyStateHeading('Передач ще не було')
            ->emptyStateDescription('Записи з’являться після оформлення акта передачі.')
            ->recordTitleAttribute('document_number')
            ->columns([
                TextColumn::make('department.name')
                    ->label('Підрозділ'),
                // ->searchable(),
                TextColumn::make('document_number')
                    ->label('Номер документа'),
                // ->searchable(),
                TextColumn::make('transferred_at')
                    ->label('Розпочато передачу')
                    ->date()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label('Передано')
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->hidden(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->hidden(),
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
