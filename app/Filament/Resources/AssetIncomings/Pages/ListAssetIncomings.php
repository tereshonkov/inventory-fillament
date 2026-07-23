<?php

namespace App\Filament\Resources\AssetIncomings\Pages;

use App\Filament\Resources\AssetIncomings\AssetIncomingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssetIncomings extends ListRecords
{
    protected static string $resource = AssetIncomingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
