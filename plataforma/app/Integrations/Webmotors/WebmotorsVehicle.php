<?php

namespace App\Integrations\Webmotors;

use App\Enums\Cambio;
use App\Enums\Carroceria;
use App\Enums\Combustivel;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

/**
 * DTO normalizado de um anúncio da Webmotors, independente do formato bruto da API.
 */
final class WebmotorsVehicle implements Arrayable
{
    public function __construct(
        public readonly string $externalId,
        public readonly string $marca,
        public readonly string $modelo,
        public readonly ?string $versao,
        public readonly int $anoFab,
        public readonly int $anoMod,
        public readonly int $km,
        public readonly float $preco,
        public readonly ?string $cambio = null,
        public readonly ?string $combustivel = null,
        public readonly ?string $carroceria = null,
        public readonly ?string $cor = null,
        public readonly ?string $motor = null,
        public readonly ?int $portas = null,
        public readonly ?string $placaFinal = null,
        /** @var list<string> */
        public readonly array $opcionais = [],
        public readonly ?string $descricao = null,
        /** @var list<string> URLs absolutas */
        public readonly array $fotos = [],
        public readonly ?string $sourceUrl = null,
        public readonly array $raw = [],
    ) {}

    /**
     * Constrói a partir do payload (normalizado) da API Site.
     * Aceita chaves em pt-BR (fixtures) ou no formato da API.
     */
    public static function fromArray(array $d): self
    {
        $get = fn (array $keys, $default = null) => collect($keys)->map(fn ($k) => data_get($d, $k))->first(fn ($v) => $v !== null && $v !== '') ?? $default;

        return new self(
            externalId: (string) $get(['external_id', 'id', 'UniqueId', 'uniqueId', 'CodigoAnuncio']),
            marca: (string) $get(['marca', 'Make', 'make', 'Specification.Make.Value']),
            modelo: (string) $get(['modelo', 'Model', 'model', 'Specification.Model.Value']),
            versao: $get(['versao', 'Version', 'version', 'Specification.Version.Value']),
            anoFab: (int) $get(['ano_fab', 'YearFabrication', 'yearFabrication', 'Specification.YearFabrication'], 0),
            anoMod: (int) $get(['ano_mod', 'YearModel', 'yearModel', 'Specification.YearModel'], 0),
            km: (int) $get(['km', 'Odometer', 'odometer', 'Specification.Odometer'], 0),
            preco: (float) $get(['preco', 'Price', 'price', 'Prices.Price'], 0),
            cambio: self::mapCambio($get(['cambio', 'Transmission', 'transmission', 'Specification.Transmission'])),
            combustivel: self::mapCombustivel($get(['combustivel', 'Fuel', 'fuel', 'Specification.Fuel'])),
            carroceria: self::mapCarroceria($get(['carroceria', 'BodyType', 'bodyType', 'Specification.BodyType'])),
            cor: $get(['cor', 'Color', 'color', 'Specification.Color.Primary']),
            motor: $get(['motor', 'Engine', 'engine']),
            portas: ($p = $get(['portas', 'NumberPorts', 'numberPorts', 'Specification.NumberPorts'])) !== null ? (int) $p : null,
            placaFinal: ($pl = $get(['placa_final', 'PlateEnd', 'plateEnd'])) !== null ? substr((string) $pl, -1) : null,
            opcionais: array_values(array_filter(array_map(fn ($o) => is_array($o) ? ($o['Name'] ?? $o['name'] ?? null) : $o, (array) $get(['opcionais', 'Optionals', 'optionals'], [])))),
            descricao: $get(['descricao', 'LongComment', 'longComment', 'Comment']),
            fotos: array_values(array_filter(array_map(fn ($f) => is_array($f) ? ($f['Url'] ?? $f['url'] ?? $f['ImageUrl'] ?? null) : $f, (array) $get(['fotos', 'Photos', 'photos', 'Media.Photos'], [])))),
            sourceUrl: $get(['source_url', 'Url', 'url', 'AdUrl']),
            raw: $d,
        );
    }

    public static function mapCambio(?string $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = Str::of($v)->lower()->ascii()->value();

        return match (true) {
            str_contains($s, 'cvt') => Cambio::Cvt->value,
            str_contains($s, 'automatiz') => Cambio::Automatizado->value,
            str_contains($s, 'auto') => Cambio::Automatico->value,
            str_contains($s, 'manual') => Cambio::Manual->value,
            default => null,
        };
    }

    public static function mapCombustivel(?string $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = Str::of($v)->lower()->ascii()->value();

        return match (true) {
            str_contains($s, 'flex') || str_contains($s, 'alcool') => Combustivel::Flex->value,
            str_contains($s, 'diesel') => Combustivel::Diesel->value,
            str_contains($s, 'hibr') || str_contains($s, 'hybr') => Combustivel::Hibrido->value,
            str_contains($s, 'eletr') || str_contains($s, 'electr') => Combustivel::Eletrico->value,
            str_contains($s, 'gnv') || str_contains($s, 'gas') => str_contains($s, 'gasol') ? Combustivel::Gasolina->value : Combustivel::Gnv->value,
            default => null,
        };
    }

    public static function mapCarroceria(?string $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = Str::of($v)->lower()->ascii()->value();

        return match (true) {
            str_contains($s, 'suv') || str_contains($s, 'utilitario esportivo') => Carroceria::Suv->value,
            str_contains($s, 'pick') || str_contains($s, 'picape') => Carroceria::Picape->value,
            str_contains($s, 'seda') => Carroceria::Sedan->value,
            str_contains($s, 'hatch') => Carroceria::Hatch->value,
            str_contains($s, 'minivan') || str_contains($s, 'van') => Carroceria::Minivan->value,
            str_contains($s, 'cupe') || str_contains($s, 'coupe') => Carroceria::Cupe->value,
            str_contains($s, 'conver') => Carroceria::Conversivel->value,
            str_contains($s, 'perua') || str_contains($s, 'wagon') => Carroceria::Perua->value,
            str_contains($s, 'utilit') => Carroceria::Utilitario->value,
            default => null,
        };
    }

    /** Campos que vão para a tabela vehicles */
    public function toVehicleAttributes(): array
    {
        return [
            'marca' => $this->marca,
            'modelo' => $this->modelo,
            'versao' => $this->versao,
            'ano_fab' => $this->anoFab,
            'ano_mod' => $this->anoMod,
            'km' => $this->km,
            'preco' => $this->preco,
            'cambio' => $this->cambio,
            'combustivel' => $this->combustivel,
            'carroceria' => $this->carroceria,
            'cor' => $this->cor,
            'motor' => $this->motor,
            'portas' => $this->portas,
            'placa_final' => $this->placaFinal,
            'opcionais' => $this->opcionais,
            'descricao' => $this->descricao,
        ];
    }

    public function hash(): string
    {
        return hash('sha256', json_encode([$this->toVehicleAttributes(), $this->fotos]));
    }

    public function toArray(): array
    {
        return $this->toVehicleAttributes() + [
            'external_id' => $this->externalId,
            'fotos' => $this->fotos,
            'source_url' => $this->sourceUrl,
        ];
    }
}
