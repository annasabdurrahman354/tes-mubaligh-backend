<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusTesKertosono : string implements HasLabel, HasColor {
    case PRA_TES = 'pra-tes';
    case TUNDA = 'tunda';
    case AKTIF = 'aktif';
    case LULUS = 'lulus';
    case TIDAK_LULUS_AKHLAK = 'tidak-lulus-akhlak';
    case TIDAK_LULUS_AKADEMIK = 'tidak-lulus-akademik';
    case PERLU_MUSYAWARAH = 'perlu-musyawarah';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PRA_TES => 'Pra-tes',
            self::AKTIF => 'Aktif',
            self::TUNDA => 'Tunda',
            self::LULUS => 'Lulus',
            self::TIDAK_LULUS_AKHLAK => 'Tidak Lulus Akhlak',
            self::TIDAK_LULUS_AKADEMIK => 'Tidak Lulus Akademik',
            self::PERLU_MUSYAWARAH => 'Perlu Musyawarah',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::PRA_TES => 'gray',
            self::AKTIF => 'primary',
            self::LULUS => 'success',
            self::TIDAK_LULUS_AKHLAK => 'danger',
            self::TIDAK_LULUS_AKADEMIK => 'danger',
            self::TUNDA => 'warning',
            self::PERLU_MUSYAWARAH => 'info',
        };
    }
}
