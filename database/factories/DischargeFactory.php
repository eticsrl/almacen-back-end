<?php

namespace Database\Factories;
use Illuminate\Support\Facades\DB;
use App\Models\Discharge;


use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Discharge>
 */
class DischargeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
           $entity=$this->faker->numberBetween(1,2);
           $tipo= $this->faker->randomElement([7, 8, 9,10,11]);
           $numero = DB::transaction(function () use ($entity, $tipo) {
            return Discharge::where('entity_id', $entity)
                ->where('tipo_documento_id', $tipo)
                ->lockForUpdate()
                ->max('numero') + 1;
        });
        return [

            'fecha_egreso'=>$this->faker->dateTimeBetween('-1 week', 'now'),
            'entity_id'=>$entity,
            'tipo_documento_id'=> $tipo,
            'numero'=>$numero,
            'receta_id'=>rand(1,500),
           // 'servicio_id'=>rand(1,15),
           // 'proveedor_id'=>rand(0,30),
            'observaciones'=>$this->faker->text(100),
            'usr'=>1,
            'estado_id'=>28,
            'usr_mod'=>1,
            'fhr_mod'=>$this->faker->dateTimeBetween('-1 week', 'now'),

        ];
    }
}
