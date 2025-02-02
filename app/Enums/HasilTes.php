<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum HasilTes : string implements HasLabel, HasColor {
    case LULUS = 'lulus';
    case TIDAK_LULUS_AKHLAK = 'tidak-lulus-akhlak';
    case TIDAK_LULUS_AKADEMIK = 'tidak-lulus-akademik';
    case PERLU_MUSYAWARAH = 'perlu-musyawarah';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::LULUS => 'Lulus',
            self::TIDAK_LULUS_AKHLAK => 'Tidak Lulus Akhlak',
            self::TIDAK_LULUS_AKADEMIK => 'Tidak Lulus Materi',
            self::PERLU_MUSYAWARAH => 'Perlu Musyawarah',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::LULUS => 'success',
            self::TIDAK_LULUS_AKHLAK => 'danger',
            self::TIDAK_LULUS_AKADEMIK => 'danger',
            self::PERLU_MUSYAWARAH => 'warning',
        };
    }
}
