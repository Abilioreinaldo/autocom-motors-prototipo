<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Carroceria: string implements HasLabel
{
    case Hatch = 'hatch';
    case Sedan = 'sedan';
    case Suv = 'suv';
    case Picape = 'picape';
    case Utilitario = 'utilitario';
    case Minivan = 'minivan';
    case Cupe = 'cupe';
    case Conversivel = 'conversivel';
    case Perua = 'perua';

    public function getLabel(): string
    {
        return match ($this) {
            self::Hatch => 'Hatch',
            self::Sedan => 'Sedã',
            self::Suv => 'SUV',
            self::Picape => 'Picape',
            self::Utilitario => 'Utilitário',
            self::Minivan => 'Minivan',
            self::Cupe => 'Cupê',
            self::Conversivel => 'Conversível',
            self::Perua => 'Perua',
        };
    }
}
