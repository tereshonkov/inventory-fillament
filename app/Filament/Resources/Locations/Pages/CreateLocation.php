<?php

namespace App\Filament\Resources\Locations\Pages;

use App\Filament\Concerns\RedirectsAfterSave;
use App\Filament\Resources\Locations\LocationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLocation extends CreateRecord
{
    use RedirectsAfterSave;

    protected static string $resource = LocationResource::class;
}
