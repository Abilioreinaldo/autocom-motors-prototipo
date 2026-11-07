<?php

namespace Database\Factories;

use App\Enums\Cambio;
use App\Enums\Carroceria;
use App\Enums\Combustivel;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Vehicle> */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        $catalogo = [
            ['Chevrolet', 'Onix', '1.0 Turbo Premier', Carroceria::Hatch],
            ['Volkswagen', 'T-Cross', '1.4 TSI Highline', Carroceria::Suv],
            ['Toyota', 'Corolla', '2.0 XEi', Carroceria::Sedan],
            ['Fiat', 'Toro', '2.0 Diesel Volcano 4x4', Carroceria::Picape],
            ['Honda', 'HR-V', '1.5 Turbo Touring', Carroceria::Suv],
            ['Jeep', 'Compass', '1.3 T270 Longitude', Carroceria::Suv],
            ['Hyundai', 'HB20', '1.0 TGDI Platinum', Carroceria::Hatch],
            ['Renault', 'Kwid', '1.0 Zen', Carroceria::Hatch],
        ];
        [$marca, $modelo, $versao, $carroceria] = $this->faker->randomElement($catalogo);
        $anoFab = $this->faker->numberBetween(2016, 2024);
        $preco = $this->faker->numberBetween(45, 210) * 1000 + 900;

        return [
            'marca' => $marca,
            'modelo' => $modelo,
            'versao' => $versao,
            'ano_fab' => $anoFab,
            'ano_mod' => $this->faker->boolean(60) ? $anoFab + 1 : $anoFab,
            'km' => $this->faker->numberBetween(5, 120) * 1000,
            'preco' => $preco,
            'preco_anterior' => $this->faker->boolean(30) ? $preco + $this->faker->numberBetween(2, 8) * 1000 : null,
            'cambio' => $this->faker->randomElement(Cambio::cases()),
            'combustivel' => $this->faker->randomElement([Combustivel::Flex, Combustivel::Flex, Combustivel::Diesel, Combustivel::Gasolina]),
            'carroceria' => $carroceria,
            'cor' => $this->faker->randomElement(['Branco', 'Preto', 'Prata', 'Cinza', 'Vermelho', 'Azul']),
            'motor' => $this->faker->randomElement(['1.0', '1.0 Turbo', '1.4 TSI', '1.5', '2.0', '2.0 Diesel']),
            'portas' => $this->faker->randomElement([2, 4]),
            'placa_final' => (string) $this->faker->numberBetween(0, 9),
            'opcionais' => $this->faker->randomElements(['Ar-condicionado', 'Direção elétrica', 'Central multimídia', 'Câmera de ré', 'Sensor de estacionamento', 'Airbags', 'Controle de estabilidade', 'Bancos em couro', 'Teto solar', 'Rodas de liga leve'], 5),
            'descricao' => $this->faker->sentence(16),
            'destaque' => $this->faker->boolean(25),
            'status' => VehicleStatus::Disponivel,
            'published_at' => now(),
        ];
    }

    public function featured(): static
    {
        return $this->state(['destaque' => true]);
    }

    public function sold(): static
    {
        return $this->state(['status' => VehicleStatus::Vendido]);
    }
}
