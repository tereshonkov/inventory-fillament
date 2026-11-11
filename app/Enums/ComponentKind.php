<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Вид комплектуючої робочої станції. */
enum ComponentKind: string implements HasLabel
{
    case MONITOR = 'monitor';
    case PC = 'pc';
    case LAPTOP = 'laptop';
    case PRINTER = 'printer';
    case KEYBOARD = 'keyboard';
    case MOUSE = 'mouse';
    case UPS = 'ups';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::MONITOR => 'Монітор',
            self::PC => 'ПК (системний блок)',
            self::LAPTOP => 'Ноутбук',
            self::PRINTER => 'Принтер',
            self::KEYBOARD => 'Клавіатура',
            self::MOUSE => 'Мишка',
            self::UPS => 'ДБЖ',
        };
    }

    /** Коротка назва для компактних елементів (плитки дашборда). */
    public function getShortLabel(): string
    {
        return match ($this) {
            self::PC => 'ПК',
            default => (string) $this->getLabel(),
        };
    }
}
