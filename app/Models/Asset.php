<?php

namespace App\Models;

use App\Enums\AssetStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

#[Fillable(['name', 'inventory_number', 'serial_number', 'notes', 'year', 'location_id', 'type_id', 'custodian_id', 'holder_id', 'status'])]
class Asset extends Model
{
    // Не забыть исправить нормальное отображение даты через миграцию!
    protected function casts(): array
    {
        return [
            'status' => AssetStatus::class,
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AssetType::class);
    }

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
