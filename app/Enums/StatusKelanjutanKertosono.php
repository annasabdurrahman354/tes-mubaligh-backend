<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusKelanjutanKertosono : string implements HasLabel, HasColor {
    case PONDOK_ASAL = 'pondok-asal';
    case LENGKONG = 'lengkong';
    case KERTOSONO = 'kertosono';
    case KEDIRI = 'kediri';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PONDOK_ASAL => 'Pondok Asal',
            self::LENGKONG => 'Lengkong',
            self::KERTOSONO => 'Kertosono',
            self::KEDIRI => 'Kediri',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::PONDOK_ASAL => 'warning',
            self::LENGKONG => 'info',
            self::KERTOSONO => 'secondary',
            self::KEDIRI => 'success',
        };
    }
}
