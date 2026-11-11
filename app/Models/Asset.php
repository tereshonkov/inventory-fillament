<?php

namespace App\Models;

use App\Enums\AssetStatus;
use App\Enums\ComponentKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
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

    /**
     * Склад станції: комплектуючі, згруповані за видом у порядку `ComponentKind`; порожні види пропускаються.
     *
     * @return Collection<string, Collection<int, AssetComponent>>
     */
    public function compositionByKind(): Collection
    {
        $components = $this->components()->with(['location', 'holder'])->orderBy('id')->get();

        // Station data is needed for the "same as the station" fallback; avoid an extra query per component.
        $components->each(fn (AssetComponent $component) => $component->setRelation('asset', $this));

        $groups = collect();

        foreach (ComponentKind::cases() as $kind) {
            $ofKind = $components->where('kind', $kind)->values();

            if ($ofKind->isNotEmpty()) {
                $groups->put($kind->value, $ofKind);
            }
        }

        return $groups;
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
