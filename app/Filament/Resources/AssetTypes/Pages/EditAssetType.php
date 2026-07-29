<?php

namespace App\Filament\Resources\AssetTypes\Pages;

use App\Filament\Concerns\RedirectsAfterSave;
use App\Filament\Resources\AssetTypes\AssetTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssetType extends EditRecord
{
    use RedirectsAfterSave;

    protected static string $resource = AssetTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
