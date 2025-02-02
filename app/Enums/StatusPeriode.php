<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusPeriode : string implements HasLabel, HasColor {
    case TES = 'tes';
    case PENDAFTARAN = 'pendaftaran';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::TES => 'Tes',
            self::PENDAFTARAN => 'Pendaftaran',
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
