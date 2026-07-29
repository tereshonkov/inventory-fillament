<?php

namespace App\Filament\Exports;

use App\Models\Asset;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\Common\Manager\Style\StyleMerger;

class AssetExporter extends Exporter
{
    protected static ?string $model = Asset::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('name')
                ->label('Назва'),
            ExportColumn::make('inventory_number')
                ->label('Інвентарний номер'),
            ExportColumn::make('serial_number')
                ->label('Серійний номер'),
            ExportColumn::make('notes')
                ->label('Нотатки'),
            ExportColumn::make('year')
                ->label('Рік'),
            ExportColumn::make('location.name')
                ->label('Місцезнаходження'),
            ExportColumn::make('type.name')
                ->label('Тип техніки'),
            ExportColumn::make('custodian.full_name')
                ->label('МВО'),
            ExportColumn::make('holder.full_name')
                ->label('Користувач'),
            ExportColumn::make('status')
                ->label('Статус')
                ->formatStateUsing(fn($state) => $state->getLabel()),
            // ExportColumn::make('created_at'),
            // ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = "Експорт активів завершено. Експортовано {$export->successful_rows} "
            . self::pluralizeRows($export->successful_rows) . '.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= " Не вдалося експортувати {$failedRowsCount} "
                . self::pluralizeRows($failedRowsCount) . '.';
        }

        return $body;
    }

    private static function pluralizeRows(int $count): string
    {
        return match (true) {
            $count % 10 === 1 && $count % 100 !== 11 => 'рядок',
            in_array($count % 10, [2, 3, 4]) && ! in_array($count % 100, [12, 13, 14]) => 'рядки',
            default => 'рядків',
        };
    }
}
