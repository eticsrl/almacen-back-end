<?php

namespace Database\Factories;
use App\Models\Entry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EntryDetail>
 */
class EntryDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cantidad = mt_rand(100, 5000);

    return [

        'ingreso_id' => Entry::inRandomOrder()->first()?->id ?? 1, // Asegura una relación válida
        'medicamento_id' => mt_rand(1, 700),
        'lote' =>$this->faker->randomNumber(6, true),
        'fecha_vencimiento' =>$this->faker->dateTimeBetween('-1 week', '+2 years'),
        'cantidad' => $cantidad,
        'costo_unitario' =>$this->faker->randomFloat(4, 0.01, 100), // Limitar los valores para mantener realismo
        'costo_total' =>$this->faker->randomFloat(2, $cantidad * 0.01, $cantidad * 100), // Costo total basado en la cantidad
        'stock_actual' => $cantidad, // stock_actual igual a cantidad
        'observaciones' =>$this->faker->text(100),
        'estado_id' => 27,
        'usr' => 1,
        'item_id' =>null,
    ];





    }
}
