<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum NilaiMateri : int implements HasLabel, HasColor {
    case _40 = 40;
    case _50 = 50;
    case _60 = 60;
    case _70 = 70;
    case _80 = 80;
    case _90 = 90;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::_40 => '40',
            self::_50 => '50',
            self::_60 => '60',
            self::_70 => '70',
            self::_80 => '80',
            self::_90 => '90',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::_40 => 'danger',
            self::_50 => 'danger',
            self::_60 => 'danger',
            self::_70 => 'warning',
            self::_80 => 'success',
            self::_90 => 'success',
        };
    }
}
