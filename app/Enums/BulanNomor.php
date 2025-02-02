<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BulanNomor : string implements HasLabel, HasColor {
    case JANUARI = '01';
    case FEBRUARI = '02';
    case MARET = '03';
    case APRIL = '04';
    case MEI = '05';
    case JUNI = '06';
    case JULI = '07';
    case AGUSTUS = '08';
    case SEPTEMBER = '09';
    case OKTOBER = '10';
    case NOVEMBER = '11';
    case DESEMBER = '12';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::JANUARI => 'Januari',
            self::FEBRUARI => 'Februari',
            self::MARET => 'Maret',
            self::APRIL => 'April',
            self::MEI => 'Mei',
            self::JUNI => 'Juni',
            self::JULI => 'Juli',
            self::AGUSTUS => 'Agustus',
            self::SEPTEMBER => 'September',
            self::OKTOBER => 'Oktober',
            self::NOVEMBER => 'November',
            self::DESEMBER => 'Desember',
        };
    }

    public static function getName($value): ?string
    {
        return match ($value) {
            self::JANUARI => 'Januari',
            self::FEBRUARI => 'Februari',
            self::MARET => 'Maret',
            self::APRIL => 'April',
            self::MEI => 'Mei',
            self::JUNI => 'Juni',
            self::JULI => 'Juli',
            self::AGUSTUS => 'Agustus',
            self::SEPTEMBER => 'September',
            self::OKTOBER => 'Oktober',
            self::NOVEMBER => 'November',
            self::DESEMBER => 'Desember',
        };
    }


    public function getColor(): ?string
    {
        return match ($this) {
            self::JANUARI => 'primary',
            self::FEBRUARI => 'primary',
            self::MARET => 'primary',
            self::APRIL => 'primary',
            self::MEI => 'primary',
            self::JUNI => 'primary',
            self::JULI => 'primary',
            self::AGUSTUS => 'primary',
            self::SEPTEMBER => 'primary',
            self::OKTOBER => 'primary',
            self::NOVEMBER => 'primary',
            self::DESEMBER => 'primary',
        };
    }
}
