<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ActivateDischargeRequest;
use App\Http\Requests\V1\DischargeRequest;
use App\Http\Resources\V1\DischargeResource;
use App\Http\Requests\V1\UpdateDischargeRequest;
use App\Models\Discharge;
use App\Models\DischargeDetail;
use App\Models\EntryDetail;



use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Services\DischargeService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DischargeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Discharge::with([
            'entity',

            'documentType',
            'supplier',
            'estate',
            'user',
            'service',
            'dischargeDetails.entryDetail.medicine.pharmaceuticalForm',
            'dischargeDetails.returns'
        ])->orderByDesc('id');

        // Filtrar SOLO por la entidad del usuario autenticado
        $query->where('entity_id', auth()->user()->entity_id);

        // Filtro por fechas

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            // Si vienen las dos fechas desde el request
            $fecha_inicio = Carbon::parse($request->fecha_inicio)->startOfDay();
            $fecha_fin    = Carbon::parse($request->fecha_fin)->endOfDay();
        } else {
            // Si no vienen, usar fecha actual
            $fecha_inicio = Carbon::today()->startOfDay();
            $fecha_fin    = Carbon::today()->endOfDay();
        }

        $query->whereBetween('fecha_egreso', [$fecha_inicio, $fecha_fin]);


        // Filtro por tipo de documento
        if ($request->filled('tipo_documento_id')) {
            $query->where('tipo_documento_id', $request->tipo_documento_id);
        }
        // Filtro por servicio
        if ($request->filled('servicio_id')) {
            $query->where('servicio_id', $request->servicio_id);
        }
        $discharges = $query->get();

        return DischargeResource::collection($discharges);
    }

    public function store(DischargeRequest $request, DischargeService $service)
    {
        //dd($request->all());

        try {
            $user = auth()->user();
            $discharge = $service->store(
                $request->validated(),
                $user->id,
                $user->entity_id
            );

            return new DischargeResource($discharge);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al guardar el egreso: ' . $e->getMessage()
            ], 422);
        }
    }

    public function show($id)
    {
        $discharge = Discharge::with([
            'entity',
            'documentType',
            'supplier',
            'estate',
            'user',
            'service',
            'personal',
            'dischargeDetails.entryDetails.medicine'
        ])->findOrFail($id);

        return new DischargeResource($discharge);
    }


    public function update(UpdateDischargeRequest $request, Discharge $discharge)
    {
        if ($discharge->estado_id !== 28) {
            return response()->json([
                'message' => 'Solo se puede modificar un egreso en estado ACTIVO.'
            ], 422);
        }

        $discharge->update([
            'fecha_egreso' => $request->fecha_egreso,
            'personal_id' => $request->personal_id,
            'tipo_documento_id' => $request->tipo_documento_id,
            'servicio_id' => $request->servicio_id,
            'proveedor_id' => $request->proveedor_id,
            'observaciones' => $request->observaciones,
            'usr_mod' => auth()->id(),
            'fhr_mod' => now()
        ]);

        return response()->json([
            'message' => 'Egreso actualizado correctamente.',
            'data' => new DischargeResource(
                $discharge->load(
                    'entity',
                    'documentType',
                    'supplier',
                    'estate',
                    'user',
                    'service',
                    'dischargeDetails.entryDetail.medicine'
                )
            )
        ]);
    }

    public function activate(ActivateDischargeRequest $request, Discharge $discharge, DischargeService $service)
    {
        try {
            $user = auth()->user();

            $activated = $service->activate(
                $discharge,
                $request->validated(),
                $user->id
            );

            return response()->json([
                'message' => 'Solicitud activada correctamente.',
                'data' => new DischargeResource($activated)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al activar la solicitud: ' . $e->getMessage()
            ], 422);
        }
    }

    public function destroy(Discharge $discharge)
    {
        //
    }

    public function recetasDispensadas(Request $request)
    {
        return response()->json([
            'message' => 'Reporte deshabilitado: la conexion secundaria fue retirada del sistema.'
        ], 410);
    }



    //Egresos por receta
    public function egresosPorReceta(Request $request)
    {
        return response()->json([
            'message' => 'Reporte deshabilitado: la conexion secundaria fue retirada del sistema.'
        ], 410);
    }
    public function medicamentosPorPaciente(Request $request)
    {
        return response()->json([
            'message' => 'Reporte deshabilitado: la conexion secundaria fue retirada del sistema.'
        ], 410);
    }
}
