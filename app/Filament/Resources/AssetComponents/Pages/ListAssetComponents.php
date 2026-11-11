<?php

namespace App\Filament\Resources\AssetComponents\Pages;

use App\Filament\Resources\AssetComponents\AssetComponentResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListAssetComponents extends ListRecords
{
    protected static string $resource = AssetComponentResource::class;

    protected Width|string|null $maxContentWidth = 'full';
}
