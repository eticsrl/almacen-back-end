<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DischargeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create();

        foreach (range(1, 200) as $i) {          // crea 200 egresos de muestra
            $data = [
                'fecha_egreso'      => $faker->dateTimeBetween('-1 week', 'now'),
                'tipo_documento_id' => $faker->randomElement([7,8,9,10,11]),
                'servicio_id'       => null,
                'proveedor_id'      => null,
                'receta_id'         => rand(1,500),
                'observaciones'     => $faker->sentence(),
                'discharge_details' => [],      // ← arma los detalles si los necesitas
            ];

            app(\App\Services\DischargeService::class)
                ->store($data, 1, 1);           // usr=1, entity=1
        }
    }
}
