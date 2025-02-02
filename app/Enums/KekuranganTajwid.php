<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum KekuranganTajwid : string implements HasLabel, HasColor {
    case DENGUNG = 'Dengung';
    case MAD = 'Mad';
    case MAKHRAJ = 'Makhraj';
    case TAFKHIM_TARQIQ = 'Tafkhim-Tarqiq';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DENGUNG => 'Dengung',
            self::MAD => 'Mad',
            self::MAKHRAJ => 'Makhraj',
            self::TAFKHIM_TARQIQ => 'Tafkhim-Tarqiq',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::DENGUNG => 'warning',
            self::MAD => 'warning',
            self::MAKHRAJ => 'warning',
            self::TAFKHIM_TARQIQ => 'warning',
        };
    }
}
