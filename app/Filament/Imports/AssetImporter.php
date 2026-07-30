<?php

namespace App\Filament\Imports;

use App\Enums\AssetStatus;
use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Employee;
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
        $notesText = trim($this->data['notes'] ?? '');
        $locations = trim($this->data['location'] ?? '');
        $typeText = trim($this->data['type'] ?? '');
        $holderText = trim($this->data['holder'] ?? '');
        $statusText = trim($this->data['status'] ?? '');

        $status = collect(AssetStatus::cases())
            ->first(fn(AssetStatus $case) => $case->getLabel() === $statusText);

        if (! $status) {
            throw new RowImportFailedException("Невідомий статус: «{$statusText}»");
        }

        return new Asset([
            'status' => $status,
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
