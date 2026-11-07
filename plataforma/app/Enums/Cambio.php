<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Cambio: string implements HasLabel
{
    case Manual = 'manual';
    case Automatico = 'automatico';
    case Cvt = 'cvt';
    case Automatizado = 'automatizado';

    public function getLabel(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Automatico => 'Automático',
            self::Cvt => 'CVT',
            self::Automatizado => 'Automatizado',
        };
    }
}
