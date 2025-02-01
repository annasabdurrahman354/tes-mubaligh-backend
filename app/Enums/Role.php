<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Role : string implements HasLabel, HasColor {
    case OPERATOR_PONDOK = 'Operator Pondok';
    case ADMIN_KEDIRI = 'Admin Kediri';
    case ADMIN_KERTOSONO = 'Admin Kertosono';
    case SUPER_ADMIN = 'Super Admin';

    case GURU_KEDIRI = 'Guru Kediri';
    case PEMBINA = 'Pembina';
    case GURU_KERTOSONO = 'Guru Kertosono';
    case KOMANDAN = 'Komandan';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::OPERATOR_PONDOK => 'Operator Pondok',
            self::ADMIN_KEDIRI => 'Admin Kediri',
            self::ADMIN_KERTOSONO => 'Admin Kertosono',
            self::SUPER_ADMIN => 'Super Admin',

            self::GURU_KEDIRI => 'Guru Kediri',
            self::PEMBINA => 'Pembina',
            self::GURU_KERTOSONO => 'Guru Kertosono',
            self::KOMANDAN => 'Komandan',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::OPERATOR_PONDOK => 'primary',
            self::ADMIN_KEDIRI => 'success',
            self::ADMIN_KERTOSONO => 'success',
            self::SUPER_ADMIN => 'info',

            self::GURU_KEDIRI => 'primary',
            self::PEMBINA => 'success',
            self::GURU_KERTOSONO => 'primary',
            self::KOMANDAN => 'success',
        };
    }
}
