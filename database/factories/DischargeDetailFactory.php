<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\EntryDetail;
use App\Models\Discharge;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DischargeDetail>
 */
class DischargeDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'egreso_id' => Discharge::inRandomOrder()->first()?->id ?? 1, // Asegura una relación válida
            'ingreso_detalle_id' => EntryDetail::inRandomOrder()->first()?->id ?? 1, // Toma un ID real existente
            'cantidad_solicitada'=> mt_rand(1,120),
            'costo_unitario'=>$this->faker->randomFloat(4),
            'costo_total'=>$this->faker->randomFloat(2),
            'observaciones'=>$this->faker->text(100),
            'usr'=>1,
            'estado_id'=>28,

        ];
    }
}
