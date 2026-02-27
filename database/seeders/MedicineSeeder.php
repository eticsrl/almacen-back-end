<?php

namespace Database\Seeders;
use App\Models\Medicine;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MedicineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Medicine::truncate();

        $csvFile = fopen(base_path("database/data/Medicamento.csv"), "r");

        $firstline = true;
        while (($data = fgetcsv($csvFile, 702, ";")) !== FALSE) {
            if (!$firstline) {
                Medicine::create([
                  "liname" => $data['0'],
                  "categoriamed_id" => $data['1'],
                  "nombre_generico" => $data['2'],
                  "formafarmaceutica_id" => $data['3'],
                  "observaciones" => $data['4'],
                  "stockmax"=> $data['5'],
                  "stockmin"=> $data['6'],
                  "darmax"=> $data['7'],
                  "darmin"=> $data['8'],
                  "estado_id" => $data['9'],
                  "usr" => $data['10'],

                 //  "estado_id"=>rand(1,3),




                ]);
            }
            $firstline = false;
        }
        fclose($csvFile);

    }

    }

