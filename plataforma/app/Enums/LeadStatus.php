<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LeadStatus: string implements HasLabel, HasColor
{
    case Novo = 'novo';
    case EmContato = 'em_contato';
    case Negociando = 'negociando';
    case Ganho = 'ganho';
    case Perdido = 'perdido';

    public function getLabel(): string
    {
        return match ($this) {
            self::Novo => 'Novo',
            self::EmContato => 'Em contato',
            self::Negociando => 'Negociando',
            self::Ganho => 'Ganho',
            self::Perdido => 'Perdido',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Novo => 'info',
            self::EmContato => 'warning',
            self::Negociando => 'primary',
            self::Ganho => 'success',
            self::Perdido => 'danger',
        };
    }

    /** Ordem de exibição no funil */
    public static function funnel(): array
    {
        return [self::Novo, self::EmContato, self::Negociando, self::Ganho, self::Perdido];
    }
}
