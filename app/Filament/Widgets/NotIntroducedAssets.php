<?php

namespace App\Filament\Widgets;

use App\Enums\AssetStatus;
use App\Models\Asset;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class NotIntroducedAssets extends TableWidget
{
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Не введено в експлуатацію')
            ->query(Asset::where('status', AssetStatus::NOT_PUT_IN_TO_OPERATION))
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('name')->label('Назва'),
                TextColumn::make('inventory_number')->label('Інвентарний номер'),
                TextColumn::make('location.name')->label('Місцезнаходження'),
            ]);
    }
}
