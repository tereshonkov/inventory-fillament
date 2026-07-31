<?php

namespace App\Filament\Resources\AssetTypes\Pages;

use App\Filament\Concerns\RedirectsAfterSave;
use App\Filament\Resources\AssetTypes\AssetTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssetType extends CreateRecord
{
    use RedirectsAfterSave;

    protected static string $resource = AssetTypeResource::class;
}
