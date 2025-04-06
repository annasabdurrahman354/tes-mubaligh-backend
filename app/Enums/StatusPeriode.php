<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusPeriode : string implements HasLabel, HasColor {
    case PENGETESAN = 'pengetesan';
    case REGISTRASI = 'registrasi';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENGETESAN => 'Pengetesan',
            self::REGISTRASI => 'Registrasi',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::PENGETESAN => 'primary',
            self::REGISTRASI => 'secondary',
        };
    }
}
