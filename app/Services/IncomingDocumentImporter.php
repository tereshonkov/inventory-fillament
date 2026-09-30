<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;

class IncomingDocumentImporter
{
    public function detectType(string $filePath): ?string
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        if (str_contains((string) $sheet->getCell('A9')->getValue(), 'НАКЛАДНА')) {
            return 'incoming';
        }

        if (str_contains((string) $sheet->getCell('A7')->getValue(), 'Акт внутрішнього переміщення')) {
            return 'transfer';
        }

        return null;
    }

    public function parseIncomingRows(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $rows = [];
        $row = 21;

        while (true) {
            $rowNumberCell = $sheet->getCell("A{$row}")->getValue();

            if (! is_numeric($rowNumberCell)) {
                break;
            }

            $rows[] = [
                'name' => trim((string) $sheet->getCell("B{$row}")->getValue()),
                'inventory_number' => trim((string) $sheet->getCell("Q{$row}")->getValue()),
                'sent_qty' => (int) $sheet->getCell("AA{$row}")->getValue(),
                'received_qty' => (int) $sheet->getCell("AC{$row}")->getValue(),
            ];

            $row++;
        }

        return $rows;
    }

    public function parseTransferRows(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $rows = [];
        $row = 16;

        while (true) {
            $name = trim((string) $sheet->getCell("A{$row}")->getValue());

            if ($name === '' || str_starts_with($name, 'Всього')) {
                break;
            }

            $rows[] = [
                'name' => $name,
                'inventory_number' => trim((string) $sheet->getCell("C{$row}")->getValue()),
                'qty' => (int) $sheet->getCell("H{$row}")->getValue(),
                'serial_number' => trim((string) $sheet->getCell("P{$row}")->getValue()),
            ];

            $row++;
        }

        return $rows;
    }

    public function importIncoming(string $filePath, int $custodianId, int $locationId): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $documentNumber = trim((string) $sheet->getCell('A9')->getValue());
        $source = trim((string) $sheet->getCell('B17')->getValue());

        $rows = $this->parseIncomingRows($filePath);
        $created = 0;

        foreach ($rows as $row) {
            $qty = $row['received_qty'] > 0 ? $row['received_qty'] : 1;

            for ($i = 0; $i < $qty; $i++) {
                $asset = \App\Models\Asset::create([
                    'name' => $row['name'],
                    'inventory_number' => $row['inventory_number'],
                    'custodian_id' => $custodianId,
                    'location_id' => $locationId,
                    'status' => \App\Enums\AssetStatus::CAPITALIZE,
                ]);

                \App\Models\AssetIncoming::create([
                    'asset_id' => $asset->id,
                    'incoming_type' => \App\Enums\IncomingType::NEW,
                    'source' => $source,
                    'document_number' => $documentNumber,
                    'received_at' => now(),
                    'completed_at' => now(),
                ]);

                $asset->update(['status' => \App\Enums\AssetStatus::NOT_PUT_IN_TO_OPERATION]);

                $created++;
            }
        }

        return ['created' => $created];
    }

    public function importTransfer(string $filePath, int $custodianId, int $locationId): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $documentNumber = trim((string) $sheet->getCell('K13')->getValue());
        $source = trim((string) $sheet->getCell('K9')->getValue());

        $rows = $this->parseTransferRows($filePath);
        $created = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            if ($row['serial_number'] !== '' && \App\Models\Asset::where('serial_number', $row['serial_number'])->exists()) {
                $skipped++;
                continue;
            }

            $qty = $row['qty'] > 0 ? $row['qty'] : 1;

            for ($i = 0; $i < $qty; $i++) {
                $asset = \App\Models\Asset::create([
                    'name' => $row['name'],
                    'inventory_number' => $row['inventory_number'],
                    'serial_number' => $i === 0 ? ($row['serial_number'] ?: null) : null,
                    'custodian_id' => $custodianId,
                    'location_id' => $locationId,
                    'status' => \App\Enums\AssetStatus::CAPITALIZE,
                ]);

                \App\Models\AssetIncoming::create([
                    'asset_id' => $asset->id,
                    'incoming_type' => \App\Enums\IncomingType::ALREADY_IN_USE,
                    'source' => $source,
                    'document_number' => $documentNumber,
                    'received_at' => now(),
                    'completed_at' => now(),
                ]);

                $asset->update(['status' => \App\Enums\AssetStatus::BALANCE]);

                $created++;
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    public function import(string $filePath, int $custodianId, int $locationId): array
    {
        $type = $this->detectType($filePath);

        return match ($type) {
            'incoming' => $this->importIncoming($filePath, $custodianId, $locationId),
            'transfer' => $this->importTransfer($filePath, $custodianId, $locationId),
            default => throw new \RuntimeException('Невідомий формат документа.'),
        };
    }
}
