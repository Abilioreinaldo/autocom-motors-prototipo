<?php

namespace App\Integrations\Webmotors;

use Illuminate\Support\Facades\Log;

/**
 * Cliente de desenvolvimento/testes: lê fixtures locais e registra leads em log.
 * IDs disponíveis nas fixtures: 54810001, 54810003, 54810021, 54810022.
 */
class FakeWebmotorsClient implements VehicleSourceInterface
{
    /** @var array<int, array> */
    protected array $fixtures;

    /** @var list<array> leads "enviados" (para asserções em testes) */
    public array $pushedLeads = [];

    public function __construct(?array $fixtures = null)
    {
        $this->fixtures = $fixtures ?? json_decode((string) file_get_contents(__DIR__.'/Fixtures/inventory.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    public function name(): string
    {
        return 'fake';
    }

    public function fetchInventory(): array
    {
        return array_map(fn (array $d) => WebmotorsVehicle::fromArray($d), $this->fixtures);
    }

    public function fetchById(string $externalId): ?WebmotorsVehicle
    {
        foreach ($this->fixtures as $d) {
            if ((string) ($d['external_id'] ?? $d['id'] ?? '') === (string) $externalId) {
                return WebmotorsVehicle::fromArray($d);
            }
        }

        return null;
    }

    public function pushLead(array $lead): array
    {
        $this->pushedLeads[] = $lead;
        Log::info('[webmotors:fake] lead enviado', $lead);

        return ['ok' => true, 'id' => 'fake-'.count($this->pushedLeads), 'message' => 'Lead registrado no cliente fake.'];
    }

    /** Substitui as fixtures em tempo de execução (útil em testes) */
    public function setFixtures(array $fixtures): static
    {
        $this->fixtures = $fixtures;

        return $this;
    }
}
