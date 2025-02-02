<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusMondok : string implements HasLabel, HasColor {
    case KIRIMAN = 'kiriman';
    case PELAJAR = 'pelajar';
    case PERSON = 'person';


    public function getLabel(): ?string
    {
        return match ($this) {
            self::PELAJAR => 'Pelajar',
            self::PERSON => 'Person',
            self::KIRIMAN => 'Kiriman',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::PELAJAR => 'warning',
            self::PERSON => 'success',
            self::KIRIMAN => 'secondary',
        };
    }
}
