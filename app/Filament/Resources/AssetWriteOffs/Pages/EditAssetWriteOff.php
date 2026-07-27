<?php

namespace App\Filament\Resources\AssetWriteOffs\Pages;

use App\Enums\AssetStatus;
use App\Filament\Resources\AssetWriteOffs\AssetWriteOffResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssetWriteOff extends EditRecord
{
    protected static string $resource = AssetWriteOffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->wasChanged('completed_at') && $this->record->completed_at !== null) {
            $this->record->asset->update([
                'status' => AssetStatus::WRITTEN_OFF,
            ]);
        }
    }
}
