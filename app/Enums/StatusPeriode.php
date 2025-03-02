<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusPeriode : string implements HasLabel, HasColor {
    case TES = 'pengetesan';
    case PENDAFTARAN = 'registrasi';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::TES => 'Pengetesan',
            self::PENDAFTARAN => 'Registrasi',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::TES => 'primary',
            self::PENDAFTARAN => 'secondary',
        };
    }
}
