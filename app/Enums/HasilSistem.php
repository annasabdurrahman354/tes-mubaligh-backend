<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum HasilSistem : string implements HasLabel, HasColor {
    case LULUS = 'lulus';
    case TIDAK_LULUS_AKADEMIK = 'tidak-lulus-akademik';
    case PERLU_MUSYAWARAH = 'perlu-musyawarah';
    case BELUM_PENGETESAN = 'belum-pengetesan';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::LULUS => 'Lulus',
            self::TIDAK_LULUS_AKADEMIK => 'Tidak Lulus Akademik',
            self::PERLU_MUSYAWARAH => 'Perlu Musyawarah',
            self::BELUM_PENGETESAN => 'Belum Pengetesan',
        };
    }

    public static function getLabelFromValue($value): ?string
    {
        return match ($value) {
            self::LULUS->value => 'Lulus',
            self::TIDAK_LULUS_AKADEMIK->value => 'Tidak Lulus Akademik',
            self::PERLU_MUSYAWARAH->value => 'Perlu Musyawarah',
            self::BELUM_PENGETESAN->value => 'Belum Pengetesan',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::LULUS => 'success',
            self::TIDAK_LULUS_AKADEMIK => 'danger',
            self::PERLU_MUSYAWARAH => 'warning',
            self::BELUM_PENGETESAN => 'gray',
        };
    }
}
