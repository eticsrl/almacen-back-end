<?php

/**
 * EJEMPLOS DE USO - ServicePersonal API
 * 
 * Este archivo contiene ejemplos de cómo usar los valores principales
 * del backend de ServicePersonal.
 */

// ============================================================================
// 1. USANDO EL CONTROLADOR (Recomendado - Ya implementado)
// ============================================================================

namespace App\Http\Controllers\Api\V1;

use App\Models\ServicePersonal;
use App\Http\Requests\StoreServicePersonalRequest;
use App\Http\Resources\ServicePersonalResource;

class ServicePersonalController extends Controller
{
    // Listar todos
    public function index()
    {
        $servicePersonals = ServicePersonal::with('documentType')->paginate(10);
        return ServicePersonalResource::collection($servicePersonals);
    }

    // Crear
    public function store(StoreServicePersonalRequest $request)
    {
        $servicePersonal = ServicePersonal::create($request->validated());
        return new ServicePersonalResource($servicePersonal->load('documentType'));
    }
}

// ============================================================================
// 2. USANDO EL SERVICIO (Para lógica más compleja - Alternativa)
// ============================================================================

namespace App\Http\Controllers\Api\V1;

use App\Services\ServicePersonalService;

class ExampleController extends Controller
{
    protected $servicePersonalService;

    public function __construct(ServicePersonalService $servicePersonalService)
    {
        $this->servicePersonalService = $servicePersonalService;
    }

    public function example()
    {
        // Obtener todos paginados
        $personals = $this->servicePersonalService->getAllPaginated(10);

        // Obtener solo activos
        $activePersonals = $this->servicePersonalService->getAllActive();

        // Crear
        $created = $this->servicePersonalService->create([
            'apellidos_nombres' => 'Juan Pérez',
            'estado' => true,
            'id_service' => 1
        ]);

        // Actualizar
        $updated = $this->servicePersonalService->update($created, [
            'estado' => false
        ]);

        // Buscar
        $searched = $this->servicePersonalService->search('Juan');

        // Obtener por ID
        $found = $this->servicePersonalService->findById(1);

        // Contar activos
        $count = $this->servicePersonalService->getActiveCount();
    }
}

// ============================================================================
// 3. USANDO EL MODELO DIRECTAMENTE (No recomendado en controllers)
// ============================================================================

namespace App\Models;

class ServicePersonal extends Model
{
    // Scopes disponibles
    $active = ServicePersonal::active()->get();
    $inactive = ServicePersonal::inactive()->get();
    $searched = ServicePersonal::search('Juan')->get();
    $byType = ServicePersonal::byDocumentType(1)->get();

    // Métodos disponibles en cada instancia
    $personal = ServicePersonal::find(1);
    $personal->activate();
    $personal->deactivate();
    $personal->isActive();
    $personal->isInactive();

    // Relaciones
    $documentType = $personal->documentType;
}

// ============================================================================
// 4. REQUESTS - VALIDACIONES
// ============================================================================

// Al crear (StoreServicePersonalRequest):
POST /api/v1/servicePersonals
{
    "apellidos_nombres": "Juan Pérez García",  // Requerido, max 255, único
    "estado": true,                             // Opcional, boolean
    "id_service": 1                             // Opcional, debe existir
}

// Al actualizar (UpdateServicePersonalRequest):
PUT /api/v1/servicePersonals/1
{
    "apellidos_nombres": "Pedro López",         // Opcional, max 255, único
    "estado": false                             // Opcional, boolean
    "id_service": 2                             // Opcional, debe existir
}

// ============================================================================
// 5. FACTORY - PARA TESTS
// ============================================================================

namespace Database\Factories;

use App\Models\ServicePersonal;

$factory = ServicePersonal::factory();

// Crear un registro
$personal = $factory->create();

// Crear 10 registros
$personals = $factory->count(10)->create();

// Con atributos específicos
$personal = $factory->create([
    'apellidos_nombres' => 'Test User',
    'estado' => false
]);

// ============================================================================
// 6. SEEDER - PARA POBLAR BASE DE DATOS
// ============================================================================

namespace Database\Seeders;

// En DatabaseSeeder.php:
$this->call(ServicePersonalSeeder::class);

// O directamente en terminal:
php artisan db:seed --class=ServicePersonalSeeder
php artisan db:seed  // Ejecuta todos los seeders

// ============================================================================
// 7. TESTS
// ============================================================================

namespace Tests\Feature;

class ServicePersonalTest extends TestCase
{
    // Ver archivo: tests/Feature/ServicePersonalTest.php
    
    // Ejecutar:
    // php artisan test tests/Feature/ServicePersonalTest.php
    // php artisan test tests/Feature/ServicePersonalTest.php --verbose
}

// ============================================================================
// 8. RESPUESTAS API
// ============================================================================

// GET /api/v1/servicePersonals (200)
{
    "success": true,
    "data": [...],
    "message": "Personal de servicio obtenido correctamente."
}

// POST /api/v1/servicePersonals (201)
{
    "success": true,
    "data": {
        "id": 1,
        "apellidos_nombres": "JUAN PÉREZ GARCÍA",
        "nombre_completo": "JUAN PÉREZ GARCÍA",
        "estado": true,
        "estado_label": "Activo",
        "id_service": 1,
        "document_type": {...},
        "is_active": true,
        "created_at": "2026-03-04 10:30:00",
        "updated_at": "2026-03-04 10:30:00"
    },
    "message": "Personal de servicio creado correctamente."
}

// Error de validación (422)
{
    "message": "The given data was invalid.",
    "errors": {
        "apellidos_nombres": ["El nombre y apellido es requerido."]
    }
}

// ============================================================================
// 9. POLÍTICAS DE AUTORIZACIÓN
// ============================================================================

// Los permisos se pueden registrar así (en seeds o servicios):
// Permission::create(['name' => 'view_service_personal']);
// Permission::create(['name' => 'create_service_personal']);
// Permission::create(['name' => 'update_service_personal']);
// Permission::create(['name' => 'delete_service_personal']);

// Los controladores pueden usar:
// $this->authorize('view', $servicePersonal);
// $this->authorize('create', ServicePersonal::class);

// ============================================================================
