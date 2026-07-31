<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Concerns\RedirectsAfterSave;
use App\Filament\Resources\Departments\DepartmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDepartment extends CreateRecord
{
    use RedirectsAfterSave;

    protected static string $resource = DepartmentResource::class;
}
