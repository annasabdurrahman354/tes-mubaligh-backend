<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Wilayah : string implements HasLabel, HasColor {
    case BARAT = 'barat';
    case TIMUR = 'timur';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::TIMUR => 'Timur',
            self::BARAT => 'Barat',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::BARAT => 'primary',
            self::TIMUR => 'secondary',
        };
    }
}
