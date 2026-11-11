<?php

namespace App\Filament\Resources\Assets\RelationManagers;

use App\Enums\ComponentKind;
use App\Models\Asset;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** Комплектуючі робочої станції; показується лише для активів категорії «Робоча станція». */
class ComponentsRelationManager extends RelationManager
{
    protected static string $relationship = 'components';

    protected static ?string $title = 'Комплектуючі';

    // Render the table together with the page: a lazy block shows an empty white area until it scrolls into view.
    protected static bool $isLazy = false;

    protected static ?string $modelLabel = 'комплектуюча';

    protected static ?string $pluralModelLabel = 'комплектуючі';

    // Components are edited right on the asset card (view page); who may do it is decided by the policy.
    public function isReadOnly(): bool
    {
        return false;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Asset
            && $ownerRecord->isWorkstation()
            && parent::canViewForRecord($ownerRecord, $pageClass);
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->components()->count();

        return $count > 0 ? (string) $count : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('kind')
                    ->label('Вид')
                    ->options(ComponentKind::class)
                    ->required(),
                TextInput::make('serial_number')
                    ->label('Серійний номер')
                    ->maxLength(255),
                Select::make('location_id')
                    ->label('Локація')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('holder_id')
                    ->label('У кого знаходиться')
                    ->relationship('holder', 'full_name')
                    ->searchable()
                    ->preload(),
                Toggle::make('is_working')
                    ->label('Справна')
                    ->default(true),
                Textarea::make('description')
                    ->label('Короткий опис')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('')
            ->emptyStateIcon(Heroicon::OutlinedComputerDesktop)
            ->emptyStateHeading('Комплектуючих ще не додано')
            ->emptyStateDescription('Додайте монітори, системний блок та іншу техніку цієї робочої станції.')
            ->recordTitleAttribute('serial_number')
            ->columns([
                TextColumn::make('kind')
                    ->label('Вид')
                    ->badge(),
                TextColumn::make('serial_number')
                    ->label('Серійний номер')
                    ->placeholder('—'),
                TextColumn::make('holder.full_name')
                    ->label('У кого')
                    ->placeholder('—'),
                TextColumn::make('location.name')
                    ->label('Локація')
                    ->placeholder('—'),
                IconColumn::make('is_working')
                    ->label('Справна')
                    ->boolean(),
                TextColumn::make('description')
                    ->label('Опис')
                    ->limit(60)
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()->label('Додати'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
