<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LeadType: string implements HasLabel
{
    case Whatsapp = 'whatsapp';
    case Proposta = 'proposta';
    case Troca = 'troca';
    case Financiamento = 'financiamento';
    case Contato = 'contato';
    case Bot = 'bot';
    case Webmotors = 'webmotors';

    public function getLabel(): string
    {
        return match ($this) {
            self::Whatsapp => 'WhatsApp',
            self::Proposta => 'Proposta',
            self::Troca => 'Troca',
            self::Financiamento => 'Financiamento',
            self::Contato => 'Contato',
            self::Bot => 'Assistente virtual',
            self::Webmotors => 'Webmotors',
        };
    }
}
