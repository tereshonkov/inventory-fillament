<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Concerns\RedirectsAfterSave;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use RedirectsAfterSave;

    protected static string $resource = UserResource::class;
}
