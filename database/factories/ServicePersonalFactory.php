<?php

namespace Database\Factories;

use App\Models\ServicePersonal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServicePersonal>
 */
class ServicePersonalFactory extends Factory
{
    protected $model = ServicePersonal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'apellidos_nombres' => $this->faker->name(),
            'estado' => $this->faker->boolean(80), // 80% de probabilidad de true
            'id_service' => null, // Puede ser null o asociado a un DocumentType
        ];
    }
}
