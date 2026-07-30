<?php

namespace App\Filament\Imports;

use App\Enums\AssetStatus;
use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Employee;
use App\Models\Location;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms\Components\Select;
use Illuminate\Support\Number;
use Override;

class AssetImporter extends Importer
{
    protected static ?string $model = Asset::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('inventory_number')
                ->rules(['max:255']),
            ImportColumn::make('serial_number')
                ->rules(['max:255']),
            ImportColumn::make('notes'),
            ImportColumn::make('location'),
            ImportColumn::make('type'),
            ImportColumn::make('holder'),
            ImportColumn::make('status')
                ->requiredMapping()
                ->rules(['required']),
        ];
    }

    #[Override]
    public static function getOptionsFormComponents(): array
    {
        return [
            Select::make('custodian_id')
                ->label('МВО, для якого імпортуємо')
                ->options(fn() => Employee::whereHas('user', fn($q) => $q->where('role', UserRole::EDITOR))->pluck('full_name', 'id'))
                ->required(),
        ];
    }

    public function resolveRecord(): Asset
    {
        $name = trim($this->data['name'] ?? '');
        $inventoryNumber = trim($this->data['inventory_number'] ?? '');
        $serialNumber = trim($this->data['serial_number'] ?? '');
        $notes = trim($this->data['notes'] ?? '');
        $locationsText = trim($this->data['location'] ?? '');
        $typeText = trim($this->data['type'] ?? '');
        $holderText = trim($this->data['holder'] ?? '');
        $statusText = trim($this->data['status'] ?? '');

        //status row
        $status = collect(AssetStatus::cases())
            ->first(fn(AssetStatus $case) => $case->getLabel() === $statusText);

        if (! $status) {
            throw new RowImportFailedException("Невідомий статус: «{$statusText}»");
        }

        //locations and notes rows
        $locationId = null;

        if ($locationsText !== '') {
            $firstLocation = trim(explode(',', $locationsText)[0]);
            $locationId = Location::where('name', $firstLocation)->value('id');

            if (str_contains($locationsText, ',')) {
                $notes = trim(($notes ? $notes . "\n\n" : '') . "[З імпорту, локація: {$locationsText}]");
            }
        }

        //type
        $typeId = null;

        if ($typeText !== '') {
            $firstType = trim(explode(',', $typeText)[0]);
            $typeId = AssetType::where('name', $firstType)->value('id');

            if (str_contains($typeText, ',')) {
                $notes = trim(($notes ? $notes . "\n\n" : '') . "[З імпорту, тип техніки: {$typeText}]");
            }
        }

        //holder

        $holderId = null;

        if ($holderText !== '') {
            $firstHolder = trim(explode(',', $holderText)[0]);
            $holderId = Employee::where('full_name', $firstHolder)->value('id');
        }

        return new Asset([
            'name' => $name,
            'inventory_number' => $inventoryNumber,
            'serial_number' => $serialNumber,
            'status' => $status,
            'notes' => $notes,
            'location_id' => $locationId,
            'type_id' => $typeId,
            'holder_id' => $holderId,
            'custodian_id' => $this->options['custodian_id'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your asset import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
