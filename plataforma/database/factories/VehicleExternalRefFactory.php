<?php

namespace Database\Factories;

use App\Models\Vehicle;
use App\Models\VehicleExternalRef;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VehicleExternalRef> */
class VehicleExternalRefFactory extends Factory
{
    protected $model = VehicleExternalRef::class;

    public function definition(): array
    {
        $id = (string) $this->faker->numberBetween(54800000, 54899999);

        return [
            'vehicle_id' => Vehicle::factory(),
            'provider' => 'webmotors',
            'external_id' => $id,
            'source_url' => "https://www.webmotors.com.br/comprar/x/y/z/{$id}",
            'payload_hash' => null,
            'locked_fields' => [],
            'synced_at' => now(),
        ];
    }
}
