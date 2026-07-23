<?php

namespace App\Models;

use App\Enums\IncomingType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'asset_id',
    'incoming_type',
    'source',
    'document_number',
    'received_at',
    'completed_at',
    'notes'
])]
class AssetIncoming extends Model
{
    protected function casts(): array
    {
        return [
            'incoming_type' => IncomingType::class,
            'received_at' => 'date',
            'completed_at' => 'date',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
