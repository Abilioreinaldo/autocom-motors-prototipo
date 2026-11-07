<?php

namespace App\Services;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\Lead;
use App\Models\Vehicle;

/**
 * Ponto único de criação de leads (site, bot, painel, Webmotors).
 */
class LeadService
{
    public function create(LeadType|string $tipo, array $data, ?Vehicle $vehicle = null, string $origem = 'site'): Lead
    {
        $tipo = $tipo instanceof LeadType ? $tipo : LeadType::from($tipo);

        return Lead::create([
            'tipo' => $tipo,
            'nome' => trim((string) ($data['nome'] ?? 'Visitante')),
            'fone' => $data['fone'] ?? null,
            'email' => $data['email'] ?? null,
            'veiculo_id' => $vehicle?->id ?? ($data['veiculo_id'] ?? null),
            'mensagem' => $data['mensagem'] ?? null,
            'status' => LeadStatus::Novo,
            'origem' => $origem,
            'meta' => array_filter($data['meta'] ?? [], fn ($v) => $v !== null && $v !== ''),
        ]);
    }

    /** Lead rápido gerado ao clicar no WhatsApp (sem formulário) */
    public function whatsappClick(?Vehicle $vehicle, array $meta = []): Lead
    {
        return $this->create(LeadType::Whatsapp, [
            'nome' => 'Clique no WhatsApp',
            'mensagem' => $vehicle ? 'Interesse em '.$vehicle->titulo_completo : 'Contato geral',
            'meta' => $meta + ['pagina' => request()->path()],
        ], $vehicle);
    }
}
