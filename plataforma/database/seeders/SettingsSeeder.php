<?php

namespace Database\Seeders;

use App\Support\Settings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        Settings::setMany([
            'nome' => 'Autocom Motors',
            'slogan' => 'Seminovos selecionados em Tatuí-SP',
            'whatsapp.numero' => config('autocom.whatsapp.numero'),
            'whatsapp.mensagem_veiculo' => config('autocom.whatsapp.mensagem_veiculo'),
            'whatsapp.mensagem_padrao' => config('autocom.whatsapp.mensagem_padrao'),
            'contato.telefone' => config('autocom.contato.telefone'),
            'contato.email' => config('autocom.contato.email'),
            'contato.endereco' => config('autocom.contato.endereco'),
            'contato.horario' => config('autocom.contato.horario'),
            'contato.instagram' => config('autocom.contato.instagram'),
            'contato.maps' => config('autocom.contato.maps'),
        ], 'loja');

        Settings::setMany([
            'financiamento.taxa_mensal' => '1.69',
            'financiamento.entrada_minima_percent' => '20',
            'financiamento.disclaimer' => config('autocom.financiamento.disclaimer'),
        ], 'financiamento');

        Settings::setMany([
            'locarx.url' => config('autocom.locarx.url'),
            'locarx.whatsapp' => config('autocom.locarx.whatsapp'),
        ], 'locarx');

        Settings::setMany([
            'bot.ativo' => '1',
            'bot.nome' => 'Assistente Autocom',
            'bot.saudacao' => 'Olá! Sou o assistente virtual da Autocom Motors. Posso ajudar a encontrar um carro, simular financiamento ou avaliar seu usado na troca. O que você procura?',
        ], 'bot');
    }
}
