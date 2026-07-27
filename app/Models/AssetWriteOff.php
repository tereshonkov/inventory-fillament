<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['asset_id', 'reason', 'document_number', 'written_off_at', 'completed_at', 'notes'])]
class AssetWriteOff extends Model
{
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
