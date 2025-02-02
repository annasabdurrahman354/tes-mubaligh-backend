<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Tahap : string implements HasLabel, HasColor {
    case KEDIRI = 'kediri';
    case KERTOSONO = 'kertosono';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::KEDIRI => 'Kediri',
            self::KERTOSONO => 'Kertosono',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::KEDIRI => 'primary',
            self::KERTOSONO => 'secondary',
        };
    }
}
