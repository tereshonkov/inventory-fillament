<?php

namespace App\Filament\Resources\AssetTransfers\Pages;

use App\Enums\AssetStatus;
use App\Filament\Concerns\RedirectsAfterSave;
use App\Filament\Resources\AssetTransfers\AssetTransferResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssetTransfer extends CreateRecord
{
    use RedirectsAfterSave;

    protected static string $resource = AssetTransferResource::class;

    protected function afterCreate(): void
    {
        $this->record->asset->update([
            'status' => AssetStatus::TRANSFERRING,
        ]);
    }
}
