<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusKelanjutanKediri : string implements HasLabel, HasColor {
    case PONDOK_ASAL = 'pondok-asal';
    case LENGKONG = 'lengkong';
    case KERTOSONO = 'kertosono';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PONDOK_ASAL => 'Pondok Asal',
            self::LENGKONG => 'Lengkong',
            self::KERTOSONO => 'Kertosono',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::PONDOK_ASAL => 'warning',
            self::LENGKONG => 'info',
            self::KERTOSONO => 'success',
        };
    }
}
