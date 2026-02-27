<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DocumentType::truncate();

        $csvFile = fopen(base_path("database/data/Documentos.csv"), "r");

        $firstline = true;
        while (($data = fgetcsv($csvFile, 67, ";")) !== FALSE) {
            if (!$firstline) {
                DocumentType::create([
                  "categoria_id" => $data['0'],
                  "descripcion" => $data['1'],
                  "cod_servicio" => $data['2'],
                  "usr" => $data['3'],
                  "estado_id" => $data['4'],

                ]);
            }
            $firstline = false;
        }
        fclose($csvFile);
    }
}
