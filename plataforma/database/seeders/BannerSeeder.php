<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            ['titulo' => 'Seminovos selecionados, procedência garantida', 'subtitulo' => 'Cada carro passa por vistoria de 120 itens antes de entrar no estoque.', 'link' => '/estoque', 'cta' => 'Ver estoque', 'posicao' => 'home_hero', 'ordem' => 0],
            ['titulo' => 'Seu usado vale mais na troca', 'subtitulo' => 'Avaliação em até 1 hora, pagamento à vista ou como entrada.', 'link' => '/#troca', 'cta' => 'Avaliar meu carro', 'posicao' => 'home_faixa', 'ordem' => 0],
            ['titulo' => 'Financiamento com as melhores taxas', 'subtitulo' => 'Simule online em segundos e receba a proposta no WhatsApp.', 'link' => '/#simulador', 'cta' => 'Simular agora', 'posicao' => 'home_faixa', 'ordem' => 1],
        ];

        foreach ($banners as $b) {
            Banner::updateOrCreate(['titulo' => $b['titulo']], $b + ['ativo' => true]);
        }
    }
}
