<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      //  \App\Models\Category::factory()->count(8)->create();

        Category::truncate();

        Category::create([
            'descripcion' => 'INGRESO'
        ]);
        Category::create([
            'descripcion' => 'EGRESO'
        ]);
        Category::create([
            'descripcion' => 'CATEGORIA MEDICAMENTOS'
        ]);
        Category::create([
            'descripcion' => 'MODALIDAD DE COMPRA'
        ]);
        Category::create([
            'descripcion' => 'ESTADO'
        ]);
        Category::create([
            'descripcion' => 'TIPO DE RECETA'
        ]);
        Category::create([
            'descripcion' => 'PAQUETE DE MEDICAMENTOS'
        ]);
        Category::create([
            'descripcion' => 'SERVICIOS'
        ]);
    }
}
