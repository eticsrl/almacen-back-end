<?php

namespace Database\Seeders;

use App\Models\ServiceClassification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceClassificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ServiceClassification::truncate();

        ServiceClassification::create([
            'servicio' => 'CONSULTAS SSU','estado_id'=>'1'
        ]);
        ServiceClassification::create([
            'servicio' => 'CONSULTA MEDICOS EXTERNOS','estado_id'=>'1'
        ]);
        ServiceClassification::create([
            'servicio' => 'CCONSULTA GUARDIA','estado_id'=>'1'
        ]);
        ServiceClassification::create([
            'servicio' => 'HOSPITALIZACION SSU','estado_id'=>'1'
        ]);
        ServiceClassification::create([
            'servicio' => 'HOSPITALIZACION EXTERNA','estado_id'=>'1'
        ]);
        ServiceClassification::create([
            'servicio' => 'CONSULTA ODONTOLOGICA','estado_id'=>'1'
        ]);
        ServiceClassification::create([
            'servicio' => 'QUIROFANO SSU','estado_id'=>'1'
        ]);
        ServiceClassification::create([
            'servicio' => 'SOLICITUDES DEL INTERIOR','estado_id'=>'1'
        ]);


    }
}
