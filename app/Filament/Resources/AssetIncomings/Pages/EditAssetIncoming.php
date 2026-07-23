<?php

namespace App\Filament\Resources\AssetIncomings\Pages;

use App\Filament\Resources\AssetIncomings\AssetIncomingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssetIncoming extends EditRecord
{
    protected static string $resource = AssetIncomingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
