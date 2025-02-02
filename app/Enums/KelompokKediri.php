<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum KelompokKediri : string implements HasLabel, HasColor {
    case A = 'A';
    case B = 'B';
    case C = 'C';
    case D = 'D';
    case E = 'E';
    case F = 'F';
    case G = 'G';
    case H = 'H';
    case I = 'I';
    case J = 'J';
    case K = 'K';
    case L = 'L';
    case M = 'M';
    case N = 'N';
    case O = 'O';
    case P = 'P';
    case Q = 'Q';
    case R = 'R';
    case S = 'S';
    case T = 'T';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::A => 'Camp A',
            self::B => 'Camp B',
            self::C => 'Camp C',
            self::D => 'Camp D',
            self::E => 'Camp E',
            self::F => 'Camp F',
            self::G => 'Camp G',
            self::H => 'Camp H',
            self::I => 'Camp I',
            self::J => 'Camp J',
            self::K => 'Camp K',
            self::L => 'Camp L',
            self::M => 'Camp M',
            self::N => 'Camp N',
            self::O => 'Camp O',
            self::P => 'Camp P',
            self::Q => 'Camp Q',
            self::R => 'Camp R',
            self::S => 'Camp S',
            self::T => 'Camp T',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::A, self::B, self::C => 'primary',
            self::D, self::E, self::F => 'secondary',
            self::G, self::H, self::I => 'success',
            self::J, self::K, self::L => 'info',
            self::M, self::N, self::O => 'warning',
            self::P, self::Q, self::R => 'danger',
            self::S, self::T => 'gray',
        };
    }
}
