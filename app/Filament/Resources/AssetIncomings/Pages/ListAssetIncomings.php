<?php

namespace App\Filament\Resources\AssetIncomings\Pages;

use App\Filament\Resources\AssetIncomings\AssetIncomingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListAssetIncomings extends ListRecords
{
    protected static string $resource = AssetIncomingResource::class;

    protected Width|string|null $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
