<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum KekuranganKeserasian : string implements HasLabel, HasColor {
    case PANJANG_PENDEK = 'Panjang Pendek';
    case IKHTILASH_HURUF_SUKUN = 'Ikhtilash Huruf Sukun';
    case IKHTILASH_HURUF_SYIDDAH = 'Ikhtilash Huruf Syiddah';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PANJANG_PENDEK => 'Panjang Pendek',
            self::IKHTILASH_HURUF_SUKUN => 'Ikhtilash Huruf Sukun',
            self::IKHTILASH_HURUF_SYIDDAH => 'Ikhtilash Huruf Syiddah',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::PANJANG_PENDEK => 'warning',
            self::IKHTILASH_HURUF_SUKUN => 'warning',
            self::IKHTILASH_HURUF_SYIDDAH => 'warning',
        };
    }
}