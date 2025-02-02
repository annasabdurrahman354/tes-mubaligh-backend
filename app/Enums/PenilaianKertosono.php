<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PenilaianKertosono : string implements HasLabel, HasColor {
    case LULUS = 'Lulus';
    case TIDAK_LULUS = 'Tidak Lulus';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::LULUS => 'Lulus',
            self::TIDAK_LULUS => 'Tidak Lulus',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::LULUS => 'success',
            self::TIDAK_LULUS => 'danger',
        };
    }
}
