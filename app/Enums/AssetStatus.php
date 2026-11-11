<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AssetStatus: string implements HasColor, HasLabel
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
            self::CAPITALIZE => 'поступає на баланс',
            self::REPAIR => 'ремонт',
        };
    }

    /**
     * Hex colors for charts, keyed by the Filament color name returned from getColor().
     * Charts cannot use Filament color names, so the badge color stays the single source of truth
     * and this table only translates it.
     */
    public const CHART_COLORS = [
        'success' => '#22c55e',
        'warning' => '#f59e0b',
        'danger' => '#ef4444',
        'info' => '#3b82f6',
        'gray' => '#6b7280',
    ];

    /** Chart color of the status: always the same semantic color as its badge. */
    public function getChartColor(): string
    {
        return self::CHART_COLORS[$this->getColor()];
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
            self::CAPITALIZE => 'info',
            self::REPAIR => 'danger',
        };
    }
}
