<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Banner> */
class BannerFactory extends Factory
{
    protected $model = Banner::class;

    public function definition(): array
    {
        return [
            'titulo' => $this->faker->sentence(4),
            'subtitulo' => $this->faker->sentence(8),
            'imagem' => null,
            'link' => '/estoque',
            'cta' => 'Ver estoque',
            'posicao' => 'home_hero',
            'ordem' => 0,
            'ativo' => true,
        ];
    }
}
