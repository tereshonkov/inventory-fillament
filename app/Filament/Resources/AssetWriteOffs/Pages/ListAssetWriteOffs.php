<?php

namespace App\Filament\Resources\AssetWriteOffs\Pages;

use App\Filament\Resources\AssetWriteOffs\AssetWriteOffResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssetWriteOffs extends ListRecords
{
    protected static string $resource = AssetWriteOffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
