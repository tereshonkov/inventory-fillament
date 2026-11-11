<?php

namespace App\Models;

use App\Enums\ComponentKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Окрема одиниця техніки, що входить до складу активу категорії «Робоча станція». */
#[Fillable(['asset_id', 'kind', 'serial_number', 'description', 'location_id', 'holder_id', 'is_working'])]
class AssetComponent extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'kind' => ComponentKind::class,
            'is_working' => 'boolean',
        ];
    }

    /** Локація: власна, а якщо не вказана — локація станції. */
    public function effectiveLocationName(): ?string
    {
        return $this->location?->name ?? $this->asset?->location?->name;
    }

    public function usesStationLocation(): bool
    {
        return $this->location_id === null;
    }

    /** Користувач: власний, а якщо не вказаний — користувач станції. */
    public function effectiveHolderName(): ?string
    {
        return $this->holder?->full_name ?? $this->asset?->holder?->full_name;
    }

    public function usesStationHolder(): bool
    {
        return $this->holder_id === null;
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'holder_id');
    }
}
