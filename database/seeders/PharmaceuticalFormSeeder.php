<?php

namespace Database\Seeders;

use App\Models\PharmaceuticalForm;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


class PharmaceuticalFormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PharmaceuticalForm::truncate();

        $csvFile = fopen(base_path("database/data/FormaFarmaceutica.csv"), "r");

        $firstline = true;
        while (($data = fgetcsv($csvFile, 68, ";")) !== FALSE) {
            if (!$firstline) {
                PharmaceuticalForm::create([
                  "formafarmaceutica" => $data['1'],
                 //  "estado_id"=>rand(1,3),
                ]);
            }
            $firstline = false;
        }
        fclose($csvFile);

    }
}
