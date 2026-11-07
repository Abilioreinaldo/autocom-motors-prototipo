<?php

namespace App\Integrations\Webmotors;

/**
 * Contrato de origem de veículos da Webmotors (API Site).
 *
 * Toda comunicação com a Webmotors passa por esta interface, de modo que a
 * aplicação não depende de um cliente concreto. Implementações:
 *  - FakeWebmotorsClient: fixtures locais (desenvolvimento, testes, demo)
 *  - ApiSiteClient: API Site oficial da Webmotors (produção)
 *
 * Scraping do site público é proibido e não é suportado por esta interface.
 */
interface VehicleSourceInterface
{
    /**
     * Lista todo o estoque do lojista na Webmotors.
     *
     * @return array<int, WebmotorsVehicle>
     */
    public function fetchInventory(): array;

    /**
     * Busca um anúncio pelo ID da Webmotors (o número no fim da URL).
     */
    public function fetchById(string $externalId): ?WebmotorsVehicle;

    /**
     * Envia um lead gerado no site para a Webmotors (quando contratado).
     *
     * @param  array{nome:string,fone?:string|null,email?:string|null,mensagem?:string|null,external_id?:string|null}  $lead
     * @return array{ok:bool,id?:string|null,message?:string|null}
     */
    public function pushLead(array $lead): array;

    public function name(): string;
}
