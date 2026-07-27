<?php

namespace App\Filament\Resources\AssetWriteOffs\Pages;

use App\Enums\AssetStatus;
use App\Filament\Resources\AssetWriteOffs\AssetWriteOffResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssetWriteOff extends CreateRecord
{
    protected static string $resource = AssetWriteOffResource::class;

    protected function afterCreate(): void
    {
        $this->record->asset->update([
            'status' => AssetStatus::WRITING_OFF,
        ]);
    }
}
