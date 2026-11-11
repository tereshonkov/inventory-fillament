<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name'])]
class AssetType extends Model
{
    use HasFactory;

    /** Категорія, активи якої складаються з окремих комплектуючих. */
    public const WORKSTATION_NAME = 'Робоча станція';
}
