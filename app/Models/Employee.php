<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['full_name', 'position', 'phone', 'status'])]
class Employee extends Model
{
    protected function casts(): array
    {
        return [
            'status' => EmployeeStatus::class
        ];
    }

    public function assetsHolder(): HasMany
    {
        return $this->hasMany(Asset::class, 'holder_id');
    }
}
