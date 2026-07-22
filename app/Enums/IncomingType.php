<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum IncomingType: string implements HasLabel, HasColor
{
    case NEW = 'new';
    case ALREADY_IN_USE = 'already_in_use';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NEW => 'отримання зі складу',
            self::ALREADY_IN_USE => 'передача з підрозділів'
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NEW => 'success',
            self::ALREADY_IN_USE => 'warning'
        };
    }
}
