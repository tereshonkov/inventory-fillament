<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum EmployeeStatus: string implements HasLabel, HasColor
{
    case ACTIVE = 'active';
    case DISMISSED = 'dismissed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'Працює',
            self::DISMISSED => 'Звільнено',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::DISMISSED => 'danger',
        };
    }
}
