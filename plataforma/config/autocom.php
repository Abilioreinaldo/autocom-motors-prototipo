<?php

/*
|--------------------------------------------------------------------------
| Autocom Motors — dados da loja
|--------------------------------------------------------------------------
| Valores padrão. Os mesmos campos podem ser sobrescritos pelo painel
| (tabela settings) através de App\Support\Settings.
*/

return [
    'nome' => env('AUTOCOM_NOME', 'Autocom Motors'),
    'slogan' => env('AUTOCOM_SLOGAN', 'Seminovos selecionados em Tatuí-SP'),

    'whatsapp' => [
        // Somente dígitos, com DDI. Ex.: 5515999990000
        'numero' => env('AUTOCOM_WHATSAPP', '5515999990000'),
        // {veiculo} é substituído por "marca modelo ano"
        'mensagem_veiculo' => 'Olá! Vi o {veiculo} no site da Autocom Motors e gostaria de mais informações.',
        'mensagem_padrao' => 'Olá! Vim pelo site da Autocom Motors e gostaria de falar com um consultor.',
    ],

    'contato' => [
        'telefone' => env('AUTOCOM_TELEFONE', '(15) 3000-0000'),
        'email' => env('AUTOCOM_EMAIL', 'contato@exemplo.com'),
        'endereco' => env('AUTOCOM_ENDERECO', 'Av. Exemplo, 1000 — Centro, Tatuí/SP'),
        'horario' => env('AUTOCOM_HORARIO', 'Seg a Sex 8h–18h · Sáb 8h–13h'),
        'instagram' => env('AUTOCOM_INSTAGRAM', 'https://instagram.com/autocommotors'),
        'maps' => env('AUTOCOM_MAPS', 'https://maps.google.com/?q=Tatu%C3%AD+SP'),
    ],

    'financiamento' => [
        // Taxa mensal padrão (1,69% a.m.). Pode ser sobrescrita em settings.
        'taxa_mensal' => (float) env('AUTOCOM_TAXA_MENSAL', 1.69),
        'entrada_minima_percent' => 20,
        'prazos' => [12, 24, 36, 48, 60],
        'disclaimer' => 'Simulação ilustrativa, sujeita à análise e condições da instituição financeira.',
    ],

    'locarx' => [
        'nome' => 'LocarX',
        'url' => env('AUTOCOM_LOCARX_URL', 'https://locarx.com.br'),
        'whatsapp' => env('AUTOCOM_LOCARX_WHATSAPP', '5515999990001'),
        'cor_fundo' => '#101010',
        'cor_destaque' => '#e23036',
    ],

    'bot' => [
        'nome' => 'Assistente Autocom',
        'ativo' => true,
    ],
];
