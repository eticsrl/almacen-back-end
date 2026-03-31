
Route::apiResource('discharges',DischargeController::class);
    En concreto, equivale a registrar estas rutas:
    GET /discharges → index (listar)
    POST /discharges → store (crear)
    GET /discharges/{discharge} → show (ver uno)
    PUT/PATCH /discharges/{discharge} → update (actualizar)
    DELETE /discharges/{discharge} → destroy (eliminar)
    Importante: apiResource no incluye create ni edit (las vistas HTML), porque está pensado para APIs JSON.

para descargo de articulos (lógica de negocio) se crea services (app/Services/DischargeService.php)
