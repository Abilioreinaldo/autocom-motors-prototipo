<?php

namespace Database\Seeders;

use App\Enums\Cambio;
use App\Enums\Carroceria;
use App\Enums\Combustivel;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

/**
 * 10 veículos DEMO claramente fictícios (preços, km e placas ilustrativos).
 * Fotos são placeholders por semente (picsum) — substitua no painel.
 */
class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $base = [
            'Ar-condicionado', 'Direção elétrica', 'Vidros elétricos', 'Travas elétricas', 'Airbags frontais', 'Freios ABS', 'Central multimídia', 'Câmera de ré',
        ];

        $lista = [
            ['Chevrolet', 'Onix', '1.0 Turbo Premier (DEMO)', 2022, 2023, 31500, 94900, 98900, Cambio::Automatico, Combustivel::Flex, Carroceria::Hatch, 'Branco', '1.0 Turbo', 4, true],
            ['Volkswagen', 'T-Cross', '1.4 TSI Highline (DEMO)', 2021, 2022, 48200, 139900, null, Cambio::Automatico, Combustivel::Flex, Carroceria::Suv, 'Cinza', '1.4 TSI', 4, true],
            ['Toyota', 'Corolla', '2.0 XEi (DEMO)', 2020, 2021, 62000, 129900, 134900, Cambio::Cvt, Combustivel::Flex, Carroceria::Sedan, 'Prata', '2.0', 4, true],
            ['Fiat', 'Toro', '2.0 Diesel Volcano 4x4 (DEMO)', 2021, 2021, 71000, 149900, null, Cambio::Automatico, Combustivel::Diesel, Carroceria::Picape, 'Vermelho', '2.0 Diesel', 4, false],
            ['Honda', 'HR-V', '1.5 Turbo Touring (DEMO)', 2023, 2023, 12800, 179900, null, Cambio::Cvt, Combustivel::Flex, Carroceria::Suv, 'Preto', '1.5 Turbo', 4, true],
            ['Jeep', 'Compass', '1.3 T270 Longitude (DEMO)', 2022, 2022, 39000, 154900, 159900, Cambio::Automatico, Combustivel::Flex, Carroceria::Suv, 'Branco', '1.3 Turbo', 4, false],
            ['Hyundai', 'HB20', '1.0 TGDI Platinum (DEMO)', 2022, 2023, 27400, 92900, null, Cambio::Automatico, Combustivel::Flex, Carroceria::Hatch, 'Azul', '1.0 Turbo', 4, false],
            ['Renault', 'Kwid', '1.0 Zen (DEMO)', 2020, 2021, 54000, 54900, 57900, Cambio::Manual, Combustivel::Flex, Carroceria::Hatch, 'Branco', '1.0', 4, false],
            ['Chevrolet', 'S10', '2.8 Diesel LTZ 4x4 (DEMO)', 2019, 2020, 98000, 169900, null, Cambio::Automatico, Combustivel::Diesel, Carroceria::Picape, 'Prata', '2.8 Diesel', 4, false],
            ['Nissan', 'Kicks', '1.6 Exclusive (DEMO)', 2021, 2022, 44300, 109900, 112900, Cambio::Cvt, Combustivel::Flex, Carroceria::Suv, 'Cinza', '1.6', 4, true],
        ];

        foreach ($lista as $i => [$marca, $modelo, $versao, $fab, $mod, $km, $preco, $ant, $cambio, $comb, $carr, $cor, $motor, $portas, $destaque]) {
            $v = Vehicle::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug("$marca $modelo $versao $mod")],
                [
                    'marca' => $marca, 'modelo' => $modelo, 'versao' => $versao,
                    'ano_fab' => $fab, 'ano_mod' => $mod, 'km' => $km,
                    'preco' => $preco, 'preco_anterior' => $ant,
                    'cambio' => $cambio, 'combustivel' => $comb, 'carroceria' => $carr,
                    'cor' => $cor, 'motor' => $motor, 'portas' => $portas,
                    'placa_final' => (string) (($i + 3) % 10),
                    'opcionais' => array_merge($base, $i % 2 ? ['Bancos em couro', 'Rodas de liga leve'] : ['Sensor de estacionamento', 'Controle de estabilidade']),
                    'descricao' => "Veículo de demonstração ($marca $modelo). Todos os dados — preço, quilometragem, placa e histórico — são fictícios e servem apenas para ilustrar a plataforma. Revisões em dia, único dono, manual e chave reserva (ilustrativo).",
                    'destaque' => $destaque,
                    'status' => 'disponivel',
                    'published_at' => now()->subDays(30 - $i * 3),
                ]
            );

            if ($v->photos()->count() === 0) {
                foreach (range(0, 3) as $n) {
                    $v->photos()->create([
                        'path' => "https://picsum.photos/seed/autocom-{$i}-{$n}/1280/800",
                        'ordem' => $n,
                        'capa' => $n === 0,
                        'alt' => "$marca $modelo — foto ".($n + 1),
                    ]);
                }
            }
        }
    }
}
