<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum KekuranganKelancaran : string implements HasLabel, HasColor {
    case KECEPATAN = 'Kecepatan';
    case KETARTILAN = 'Ketartilan';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::KECEPATAN => 'Kecepatan',
            self::KETARTILAN => 'Ketartilan',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::KECEPATAN => 'warning',
            self::KETARTILAN => 'warning',
        };
    }
}
