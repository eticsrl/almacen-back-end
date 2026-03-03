<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EntityController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\DocumentTypeController;
use App\Http\Controllers\Api\V1\PharmaceuticalFormController;
use App\Http\Controllers\Api\V1\MedicineController;
use App\Http\Controllers\Api\V1\PersonalController;
use App\Http\Controllers\Api\V1\MedicinePackageController;
use App\Http\Controllers\Api\V1\EntryController;
use App\Http\Controllers\Api\V1\DischargeController;
use App\Http\Controllers\Api\V1\InventarioResumenController;
use App\Http\Controllers\Api\V1\DischargeReportController;
use App\Http\Controllers\Api\V1\EntryReportController;
use App\Http\Controllers\Api\V1\DischageMedicinesReportController;
use App\Http\Controllers\Api\V1\ServicePersonalController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\UserController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

    /*Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();});*/
    Route::post('auth/register',[AuthController::class,'create']);
    Route::post('auth/login',[AuthController::class,'login']);

        Route::middleware(['auth:sanctum'])->group(function(){


        Route::apiResource('categories',CategoryController::class);
        Route::apiResource('suppliers',SupplierController::class);
        Route::apiResource('documentTypes',DocumentTypeController::class);

        // Users, Roles and Permissions endpoints
        Route::apiResource('users', UserController::class);
        Route::post('users/{id}/roles', [UserController::class, 'assignRoles']);
        Route::get('users/{id}/roles', [UserController::class, 'getRoles']);

        Route::apiResource('roles', RoleController::class);
        Route::apiResource('permissions', PermissionController::class);
        Route::post('roles/{id}/permissions', [RoleController::class, 'assignPermissions']);
        Route::get('roles/{id}/permissions', [RoleController::class, 'getPermissions']);

        Route::apiResource('entities',EntityController::class);
        Route::apiResource('pharmaceuticalForms',PharmaceuticalFormController::class);
        Route::apiResource('medicines',MedicineController::class);
        Route::apiResource('personal',PersonalController::class);
        Route::apiResource('servicePersonals',ServicePersonalController::class);

        Route::apiResource('medicinePackages',MedicinePackageController::class);
        Route::get('entries/with-stock', [EntryController::class, 'entryDetailsConStock']);
        Route::get('entries/reentry', [EntryController::class, 'entryDetailsForReentry']);
        Route::post('entries/returns', [EntryController::class, 'storeReturn']);
        Route::get('entries/lots', [EntryController::class, 'lotsByMedicine']);
        Route::post('entries/{entry_id}/activate', [EntryController::class, 'activate']);
        Route::apiResource('entries', EntryController::class);

        Route::get('/discharges/recetas-dispensadas', [DischargeController::class, 'recetasDispensadas']);
        Route::get('discharges/egresosReceta', [DischargeController::class, 'egresosPorReceta']);
        Route::apiResource('discharges',DischargeController::class);
        /*Reportes*/
        Route::get('reportes/inventario/resumen', [InventarioResumenController::class, 'index'])
        ->name('reportes.inventario.resumen');
        Route::get('reportes/inventario/resumen/movimientos', [InventarioResumenController::class, 'movimientos']);

        Route::get('reportes/egresos-por-fecha', [DischargeReportController::class, 'porFecha']);
        Route::get('reportes/pacientes/medicamentos', [DischargeController::class, 'medicamentosPorPaciente']);

        Route::get('/reportes/ingresos-por-fecha', [EntryReportController::class, 'porFecha']);
        Route::get('/reportes/reingresos-por-receta', [EntryReportController::class, 'reingresosPorReceta']);

        Route::get('reportes/egresos/medicamentos-por-especialidad',[DischageMedicinesReportController::class, 'index']
        )->name('reportes.egresos.medicamentos-por-especialidad');


        Route::post('auth/logout',[AuthController::class,'logout']);
        Route::get('user/profile', [AuthController::class, 'profile']);
    });


