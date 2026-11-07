<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VehicleStatus: string implements HasLabel, HasColor
{
    case Disponivel = 'disponivel';
    case Reservado = 'reservado';
    case Vendido = 'vendido';

    public function getLabel(): string
    {
        return match ($this) {
            self::Disponivel => 'Disponível',
            self::Reservado => 'Reservado',
            self::Vendido => 'Vendido',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Disponivel => 'success',
            self::Reservado => 'warning',
            self::Vendido => 'gray',
        };
    }
}
