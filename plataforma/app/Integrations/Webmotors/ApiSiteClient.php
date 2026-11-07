<?php

namespace App\Integrations\Webmotors;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente da API Site da Webmotors (integração oficial para lojistas).
 *
 * Endpoints e nomes de campos seguem a documentação contratada pelo lojista;
 * ajuste os caminhos abaixo conforme a versão habilitada na sua conta.
 * Nenhuma página pública é acessada: apenas a API autenticada.
 */
class ApiSiteClient implements VehicleSourceInterface
{
    public function __construct(
        protected string $baseUrl,
        protected ?string $clientId,
        protected ?string $clientSecret,
        protected ?string $dealerId,
        protected int $timeout = 20,
    ) {}

    public static function fromConfig(): self
    {
        $c = config('webmotors.api');

        return new self(
            baseUrl: rtrim((string) $c['base_url'], '/'),
            clientId: $c['client_id'] ?? null,
            clientSecret: $c['client_secret'] ?? null,
            dealerId: $c['dealer_id'] ?? null,
            timeout: (int) ($c['timeout'] ?? 20),
        );
    }

    public function name(): string
    {
        return 'api';
    }

    public function fetchInventory(): array
    {
        $items = [];
        $page = 1;

        do {
            $res = $this->http()->get('/dealers/'.$this->dealerId.'/vehicles', ['page' => $page, 'pageSize' => 100]);
            $res->throw();
            $json = $res->json();
            $batch = $json['data'] ?? $json['Vehicles'] ?? $json['items'] ?? (array_is_list($json) ? $json : []);
            foreach ($batch as $row) {
                $items[] = WebmotorsVehicle::fromArray($row);
            }
            $hasMore = (bool) ($json['hasNext'] ?? $json['HasNext'] ?? (count($batch) === 100));
            $page++;
        } while ($hasMore && $page <= 50);

        return $items;
    }

    public function fetchById(string $externalId): ?WebmotorsVehicle
    {
        $res = $this->http()->get('/dealers/'.$this->dealerId.'/vehicles/'.$externalId);
        if ($res->status() === 404) {
            return null;
        }
        $res->throw();
        $json = $res->json();

        return WebmotorsVehicle::fromArray($json['data'] ?? $json);
    }

    public function pushLead(array $lead): array
    {
        $res = $this->http()->post('/dealers/'.$this->dealerId.'/leads', [
            'name' => $lead['nome'],
            'phone' => $lead['fone'] ?? null,
            'email' => $lead['email'] ?? null,
            'message' => $lead['mensagem'] ?? null,
            'vehicleId' => $lead['external_id'] ?? null,
        ]);

        return [
            'ok' => $res->successful(),
            'id' => $res->json('id') ?? $res->json('data.id'),
            'message' => $res->successful() ? null : $res->body(),
        ];
    }

    protected function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->acceptJson()
            ->withToken($this->token())
            ->retry(2, 500, throw: false);
    }

    /** OAuth client-credentials, token em cache até expirar. */
    protected function token(): string
    {
        if (! $this->clientId || ! $this->clientSecret) {
            throw new RuntimeException('Credenciais da API Site da Webmotors não configuradas (WEBMOTORS_CLIENT_ID / WEBMOTORS_CLIENT_SECRET).');
        }

        return Cache::remember('webmotors.api.token', now()->addMinutes(50), function () {
            $res = Http::baseUrl($this->baseUrl)->timeout($this->timeout)->asForm()->post('/oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);
            $res->throw();

            return (string) ($res->json('access_token') ?? throw new RuntimeException('Resposta de autenticação da Webmotors sem access_token.'));
        });
    }
}
