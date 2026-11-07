<?php

namespace Database\Seeders;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\Lead;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    public function run(): void
    {
        if (Lead::count() > 0) {
            return;
        }

        $veiculos = Vehicle::all();
        $exemplos = [
            ['tipo' => LeadType::Whatsapp, 'nome' => 'Cliente Demo 1', 'status' => LeadStatus::Novo, 'dias' => 0],
            ['tipo' => LeadType::Proposta, 'nome' => 'Cliente Demo 2', 'status' => LeadStatus::EmContato, 'dias' => 1, 'meta' => ['valor_proposta' => 125000]],
            ['tipo' => LeadType::Financiamento, 'nome' => 'Cliente Demo 3', 'status' => LeadStatus::Negociando, 'dias' => 2, 'meta' => ['entrada' => 30000, 'prazo' => 48, 'parcela' => 2890.55]],
            ['tipo' => LeadType::Troca, 'nome' => 'Cliente Demo 4', 'status' => LeadStatus::Novo, 'dias' => 3, 'meta' => ['troca' => ['marca' => 'Fiat', 'modelo' => 'Argo', 'ano' => 2019, 'km' => 60000]]],
            ['tipo' => LeadType::Contato, 'nome' => 'Cliente Demo 5', 'status' => LeadStatus::Ganho, 'dias' => 6],
            ['tipo' => LeadType::Bot, 'nome' => 'Cliente Demo 6', 'status' => LeadStatus::Perdido, 'dias' => 9],
            ['tipo' => LeadType::Webmotors, 'nome' => 'Cliente Demo 7', 'status' => LeadStatus::Novo, 'dias' => 1],
        ];

        foreach ($exemplos as $i => $e) {
            $lead = Lead::create([
                'tipo' => $e['tipo'],
                'nome' => $e['nome'],
                'fone' => '(15) 99999-00'.str_pad((string) ($i + 10), 2, '0', STR_PAD_LEFT),
                'email' => 'cliente'.($i + 1).'@exemplo.com',
                'veiculo_id' => $veiculos->get($i % max($veiculos->count(), 1))?->id,
                'mensagem' => 'Lead de demonstração — dados fictícios.',
                'status' => LeadStatus::Novo,
                'origem' => $e['tipo'] === LeadType::Bot ? 'bot' : 'site',
                'meta' => $e['meta'] ?? [],
            ]);
            $lead->created_at = now()->subDays($e['dias']);
            $lead->status = $e['status'];
            $lead->save();
        }
    }
}
