<?php

namespace App\Models;

use App\Enums\AssetStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'inventory_number', 'serial_number', 'notes', 'year', 'location_id', 'type_id', 'custodian_id', 'holder_id', 'status'])]
class Asset extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => AssetStatus::class,
        ];
    }

    /**
     * Short single-line name for headings and compact widgets: whitespace and
     * paragraph breaks are collapsed, then cut to 4 words (and 60 characters
     * as a guard against one extremely long "word").
     */
    protected function shortName(): Attribute
    {
        return Attribute::get(fn (): string => (string) Str::of((string) $this->name)
            ->squish()
            ->words(4, '…')
            ->limit(60, '…'));
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

    public function components(): HasMany
    {
        return $this->hasMany(AssetComponent::class);
    }

    /** Чи належить актив до категорії «Робоча станція» (має комплектуючі). */
    public function isWorkstation(): bool
    {
        return $this->type?->name === AssetType::WORKSTATION_NAME;
    }

    public function incomings(): HasMany
    {
        return $this->hasMany(AssetIncoming::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(AssetTransfer::class);
    }

    public function writeOffs(): HasMany
    {
        return $this->hasMany(AssetWriteOff::class);
    }
}
