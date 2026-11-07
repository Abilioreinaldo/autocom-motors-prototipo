<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Combustivel: string implements HasLabel
{
    case Flex = 'flex';
    case Gasolina = 'gasolina';
    case Diesel = 'diesel';
    case Hibrido = 'hibrido';
    case Eletrico = 'eletrico';
    case Gnv = 'gnv';

    public function getLabel(): string
    {
        return match ($this) {
            self::Flex => 'Flex',
            self::Gasolina => 'Gasolina',
            self::Diesel => 'Diesel',
            self::Hibrido => 'Híbrido',
            self::Eletrico => 'Elétrico',
            self::Gnv => 'GNV',
        };
    }
}
