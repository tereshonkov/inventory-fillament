<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;

class AssetActivationImporter
{
    public function findBlockStarts(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $starts = [];

        for ($row = 1; $row <= $sheet->getHighestRow(); $row++) {
            $value = (string) $sheet->getCell("A{$row}")->getValue();

            if (str_contains($value, 'Акт введення в експлуатацію')) {
                $starts[] = $row;
            }
        }

        return $starts;
    }

    public function findBlockBoundaries(string $filePath, int $blockStart): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $headerRow = null;
        $totalRow = null;
        $nameRow = null;

        for ($row = $blockStart; $row <= $highestRow; $row++) {
            $value = (string) $sheet->getCell("A{$row}")->getValue();

            if ($headerRow === null && str_contains($value, 'Інвентарний (номенклатурний) номер')) {
                $headerRow = $row;
            }

            if ($totalRow === null && str_starts_with($value, 'Всього:')) {
                $totalRow = $row;
            }

            if ($nameRow === null && str_contains($value, 'Проведено огляд')) {
                $nameRow = $row;
                break; // знайшли останній потрібний орієнтир, далі шукати нема сенсу
            }
        }

        return [
            'header_row' => $headerRow,
            'total_row' => $totalRow,
            'name_row' => $nameRow,
        ];
    }

    public function parseBlockRows(string $filePath, int $headerRow, int $totalRow): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $rows = [];
        $row = $headerRow + 3; // +1 заголовок, +1 рядок-нумерація "1,2,3..."

        while ($row < $totalRow) {
            $rows[] = [
                'inventory_number' => trim((string) $sheet->getCell("A{$row}")->getValue()),
                'qty' => (int) $sheet->getCell("D{$row}")->getValue(),
                'serial_number' => trim((string) $sheet->getCell("AL{$row}")->getValue()),
            ];

            $row++;
        }

        return $rows;
    }

    public function getBlockName(string $filePath, int $nameRow): string
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        return trim((string) $sheet->getCell("H{$nameRow}")->getValue());
    }

    public function activate(string $filePath): array
    {
        $activated = 0;
        $skipped = [];

        foreach ($this->findBlockStarts($filePath) as $blockStart) {
            $boundaries = $this->findBlockBoundaries($filePath, $blockStart);

            if ($boundaries['header_row'] === null || $boundaries['total_row'] === null) {
                continue;
            }

            $blockName = $boundaries['name_row'] !== null
                ? $this->getBlockName($filePath, $boundaries['name_row'])
                : 'Невідома назва';

            $rows = $this->parseBlockRows($filePath, $boundaries['header_row'], $boundaries['total_row']);

            foreach ($rows as $row) {
                if ($row['serial_number'] === '' || $row['serial_number'] === 'б/н') {
                    $skipped[] = [
                        'block_name' => $blockName,
                        'inventory_number' => $row['inventory_number'],
                    ];
                    continue;
                }

                $asset = \App\Models\Asset::where('serial_number', $row['serial_number'])->first();

                if ($asset === null) {
                    $skipped[] = [
                        'block_name' => $blockName,
                        'inventory_number' => $row['inventory_number'],
                        'reason' => "не знайдено актив із серійником {$row['serial_number']}",
                    ];
                    continue;
                }

                if ($row['serial_number'] === '' || $row['serial_number'] === 'б/н') {
                    $skipped[] = [
                        'block_name' => $blockName,
                        'inventory_number' => $row['inventory_number'],
                        'reason' => 'немає серійного номера в документі',
                    ];
                    continue;
                }

                $asset->update([
                    'inventory_number' => $row['inventory_number'],
                    'status' => \App\Enums\AssetStatus::BALANCE,
                ]);

                $activated++;
            }
        }

        return [
            'activated' => $activated,
            'skipped' => $skipped,
        ];
    }
}
