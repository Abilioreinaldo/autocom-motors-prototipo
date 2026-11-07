<?php

/*
|--------------------------------------------------------------------------
| Integração Webmotors (API Site)
|--------------------------------------------------------------------------
| driver: "fake" usa fixtures locais (App\Integrations\Webmotors\FakeWebmotorsClient);
|         "api" usa a API Site oficial (App\Integrations\Webmotors\ApiSiteClient).
| A integração é desligada por padrão. Scraping do site da Webmotors é proibido;
| somente a API oficial contratada pelo lojista é suportada.
*/

return [
    'enabled' => (bool) env('WEBMOTORS_ENABLED', false),
    'driver' => env('WEBMOTORS_DRIVER', 'fake'),

    'api' => [
        'base_url' => env('WEBMOTORS_API_URL', 'https://api.webmotors.com.br/apisite/v1'),
        'client_id' => env('WEBMOTORS_CLIENT_ID'),
        'client_secret' => env('WEBMOTORS_CLIENT_SECRET'),
        'dealer_id' => env('WEBMOTORS_DEALER_ID'),
        'timeout' => (int) env('WEBMOTORS_TIMEOUT', 20),
    ],

    'sync' => [
        // Intervalo do agendamento (minutos). O scheduler usa everyThirtyMinutes().
        'interval_minutes' => 30,
        // Tolerância de km para detectar duplicidade (marca+modelo+ano+km ±500)
        'km_tolerance' => 500,
        // Status aplicado a veículos que sumiram do feed
        'missing_status' => 'vendido',
    ],

    // Domínios aceitos para "Importar por link"
    'allowed_hosts' => ['webmotors.com.br', 'www.webmotors.com.br'],
];
