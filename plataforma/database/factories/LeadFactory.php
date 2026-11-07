<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Lead> */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'tipo' => $this->faker->randomElement(LeadType::cases()),
            'nome' => $this->faker->name(),
            'fone' => '15 9'.$this->faker->numerify('####-####'),
            'email' => $this->faker->optional()->safeEmail(),
            'veiculo_id' => null,
            'mensagem' => $this->faker->optional()->sentence(),
            'status' => $this->faker->randomElement(LeadStatus::cases()),
            'origem' => 'site',
            'meta' => [],
        ];
    }
}
