<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum AssetStatus: string implements HasLabel, HasColor
{
    case BALANCE = 'balance';
    case TRANSFERRING = 'transferring';
    case TRANSFERRED = 'transferred';
    case LOST = 'lost';
    case WRITTEN_OFF = 'written_off';
    case NOT_PUT_IN_TO_OPERATION = 'not_put_in_to_operation';
    case WRITING_OFF = 'writing_off';
    case CAPITALIZE = 'capitalize';
    case REPAIR = 'repair';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::BALANCE => 'на балансі',
            self::TRANSFERRING => 'проводиться передача',
            self::TRANSFERRED => 'передано',
            self::LOST => 'втрачено',
            self::WRITTEN_OFF => 'списано',
            self::NOT_PUT_IN_TO_OPERATION => 'не введено',
            self::WRITING_OFF => 'проводиться списання',
            self::CAPITALIZE => 'надходить на баланс',
            self::REPAIR => 'ремонт',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::BALANCE => 'success',
            self::TRANSFERRING => 'warning',
            self::TRANSFERRED => 'gray',
            self::LOST => 'danger',
            self::WRITTEN_OFF => 'gray',
            self::NOT_PUT_IN_TO_OPERATION => 'info',
            self::WRITING_OFF => 'warning',
            self::CAPITALIZE => 'warning',
            self::REPAIR => 'warning',
        };
    }
}
