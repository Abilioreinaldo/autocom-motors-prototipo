<?php

namespace Database\Factories;

use App\Models\Vehicle;
use App\Models\VehiclePhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VehiclePhoto> */
class VehiclePhotoFactory extends Factory
{
    protected $model = VehiclePhoto::class;

    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'path' => 'https://picsum.photos/seed/'.$this->faker->uuid().'/1280/800',
            'disk' => 'public',
            'ordem' => 0,
            'capa' => false,
        ];
    }
}
