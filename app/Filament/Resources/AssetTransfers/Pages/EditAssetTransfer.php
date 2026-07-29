<?php

namespace App\Filament\Resources\AssetTransfers\Pages;

use App\Enums\AssetStatus;
use App\Filament\Concerns\RedirectsAfterSave;
use App\Filament\Resources\AssetTransfers\AssetTransferResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssetTransfer extends EditRecord
{
    use RedirectsAfterSave;

    protected static string $resource = AssetTransferResource::class;

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
                'status' => AssetStatus::TRANSFERRED,
            ]);
        }
    }
}
