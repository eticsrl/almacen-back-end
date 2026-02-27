<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\PharmaceuticalForm;
use App\Models\Category;
use App\Models\Document_type;
use App\Models\Medicine;
use App\Models\MedicineEntity;
use App\Models\MedicinePackage;
use App\Models\Entry;
use App\Models\EntryDetail;
use App\Models\Discharge;
use App\Models\DischargeDetail;
use Illuminate\Database\Seeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;
use App\Services\DischargeService;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

       \App\Models\Supplier::factory()->count(30)->create();


      Schema::disableForeignKeyConstraints();

       $classes =[PharmaceuticalFormSeeder::class,
                UserSeeder::class,
                CategorySeeder::class,
                EntitySeeder::class,
                DocumentTypeSeeder::class,
                MedicineSeeder::class,
                ServiceClassificationSeeder::class,


       ];

           $this->call($classes);

           \App\Models\MedicinePackage::factory()->count(139)->create();



Entry::factory()
->count(100)
->create()
->each(function ($entry) {
    EntryDetail::factory()
        ->count(rand(3, 5)) // mínimo 1 por ingreso
        ->create([
            'ingreso_id' => $entry->id,
            'item_id' => null, // es un ingreso original
        ]);
});


$dischargeService = app(DischargeService::class);
$userId   = 1;   // o el que corresponda al admin de pruebas
$entityId = 1;

foreach (range(1, 100) as $i) {

    /* a.  Datos básicos del egreso  ---------------------------------- */
    $tipoDocumento = Arr::random([7, 8, 9, 10, 11]);     // tus IDs reales
    $fechaEgreso   = now()->subDays(rand(0, 7));

    /* b.  Armamos los detalles  -------------------------------------- */
    $detalles = [];

    // elegimos 1-4 ítems aleatorios que **tengan stock_actual > 0**
    $candidatos = EntryDetail::where('stock_actual', '>', 0)
                  ->inRandomOrder()
                  ->take(rand(1, 4))
                  ->get();

    foreach ($candidatos as $entry) {
        $cantSolicitada = rand(1, min(5, $entry->stock_actual));

        $detalles[] = [
            'ingreso_detalle_id' => $entry->id,
            'cantidad_solicitada'=> $cantSolicitada,
            'costo_unitario'     => $entry->costo_unitario,
            'costo_total'        => $cantSolicitada * $entry->costo_unitario,
            'observaciones'      => 'Seed auto',
        ];
    }

    /* c.  Llamamos al servicio  -------------------------------------- */
    $dischargeService->store([
        'fecha_egreso'      => $fechaEgreso,
        'tipo_documento_id' => $tipoDocumento,
        'servicio_id'       => null,
        'proveedor_id'      => null,
        'receta_id'         => rand(1, 500),
        'observaciones'     => 'Egreso de pruebas n. ' . $i,
        'discharge_details' => $detalles,
    ], $userId, $entityId);
}

    }
}
