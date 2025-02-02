<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum KekuranganKhusus : string implements HasLabel, HasColor {
    case HARAKAT = 'Harakat';
    case LAFADZ = 'Lafadz';
    case LAM_JALALAH = 'Lam Jalalah';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::HARAKAT => 'Harakat',
            self::LAFADZ => 'Lafadz',
            self::LAM_JALALAH => 'Lam Jalalah',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::HARAKAT => 'warning',
            self::LAFADZ => 'warning',
            self::LAM_JALALAH => 'warning',
        };
    }
}
