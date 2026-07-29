<?php

namespace App\Filament\Resources\AssetIncomings\Pages;

use App\Enums\AssetStatus;
use App\Enums\IncomingType;
use App\Filament\Resources\AssetIncomings\AssetIncomingResource;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

class EditAssetIncoming extends EditRecord
{
    protected static string $resource = AssetIncomingResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('incoming_type')
                    ->label('Тип надходження')
                    ->options(IncomingType::class)
                    ->required(),
                TextInput::make('source')
                    ->label('Джерело надходження'),
                TextInput::make('document_number')
                    ->label('Номер документу'),
                DatePicker::make('received_at')
                    ->label('Розпочато отримання')
                    ->native(false)
                    ->required(),
                DatePicker::make('completed_at')
                    ->label('Отримано')
                    ->native(false),
                Textarea::make('notes')
                    ->label('Нотатки')
                    ->columnSpanFull(),
            ]);
    }

    protected function afterSave(): void
    {
        if ($this->record->wasChanged('completed_at') && $this->record->completed_at !== null) {
            $this->record->asset->update([
                'status' => $this->record->incoming_type === IncomingType::NEW
                    ? AssetStatus::NOT_PUT_IN_TO_OPERATION
                    : AssetStatus::BALANCE,
            ]);
        }
    }
}
