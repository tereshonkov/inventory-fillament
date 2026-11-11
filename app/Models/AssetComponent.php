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
