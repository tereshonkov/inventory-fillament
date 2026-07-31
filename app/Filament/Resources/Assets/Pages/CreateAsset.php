<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Concerns\RedirectsAfterSave;
use App\Filament\Resources\Assets\AssetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAsset extends CreateRecord
{
    use RedirectsAfterSave;

    protected static string $resource = AssetResource::class;
}
