<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Resources\Assets\AssetResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewAsset extends ViewRecord
{
    protected static string $resource = AssetResource::class;

    public function getHeading(): string|Htmlable
    {
        // Long names (several paragraphs are possible) are shortened here; the full name is in the infolist.
        return $this->getRecord()->short_name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return filled($this->getRecord()->inventory_number)
            ? '№ '.$this->getRecord()->inventory_number
            : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
