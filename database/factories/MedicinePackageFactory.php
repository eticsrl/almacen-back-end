<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MedicinePackage>
 */
class MedicinePackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'documento_id'=>rand(38,44),
            'medicamento_id'=>rand(1,700),
            'cantidad'=>rand(10,30),
            'observaciones'=>$this->faker->text(100),
            'dias'=>rand(1,5),
            'usr'=>1,
            'estado_id'=>rand(27,28),

        ];
    }
}
