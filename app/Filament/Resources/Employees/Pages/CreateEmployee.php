<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Concerns\RedirectsAfterSave;
use App\Filament\Resources\Employees\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    use RedirectsAfterSave;

    protected static string $resource = EmployeeResource::class;
}
