<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum JenisKelamin : string implements HasLabel, HasColor {
    case LAKI_LAKI  = 'L';
    case PEREMPUAN  = 'P';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::LAKI_LAKI => 'Laki-laki',
            self::PEREMPUAN => 'Perempuan',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::LAKI_LAKI => 'success',
            self::PEREMPUAN => 'primary',
        };
    }
}
