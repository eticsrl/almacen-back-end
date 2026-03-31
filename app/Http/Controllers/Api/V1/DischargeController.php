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
        $sissuDb = config('database.connections.mysql_sissu.database');
        $afDb    = config('database.connections.mysql_afiliacion.database');
        $farmDb  = config('database.connections.mysql.database');

        // Soporta ambos nombres desde el front
        $fechaInicio = $request->input('inicio') ?? $request->input('fechaInicio');
        $fechaFin    = $request->input('fin')    ?? $request->input('fechaFin');

        // Entidad objetivo: por parámetro o la del usuario autenticado
        $targetEntityId = (int) ($request->integer('entity_id') ?: auth()->user()->entity_id);

        $ini = $fechaInicio ? \Carbon\Carbon::parse($fechaInicio)->startOfDay() : null;
        $fin = $fechaFin    ? \Carbon\Carbon::parse($fechaFin)->endOfDay()   : null;

        /* 0) Obtener receta_id que tengan ≥1 egreso en Farmacia para esa entidad (y rango por fecha_egreso si llega) */
        $recetaIds = DB::connection('mysql')
            ->table('discharges as d')
            ->where('d.tipo_documento_id', 10)          // egreso por receta
            ->where('d.entity_id', $targetEntityId)     // misma entidad
            ->when($ini && $fin, fn($q) => $q->whereBetween('d.fecha_egreso', [$ini, $fin]))
            ->pluck('d.receta_id')
            ->filter()      // saca nulls
            ->unique()
            ->values()
            ->all();

        if (empty($recetaIds)) {
            return response()->json([]); // no hay recetas con egresos
        }

        /* 1) Recetas (SISSU) SOLO de las que tienen egresos + filtros de cabecera */
        $q = DB::connection('mysql_sissu')
            ->table("{$sissuDb}.recetas as r")
            ->leftJoin(DB::raw("`{$afDb}`.`persona_afiliado_entidads` as pae"), 'r.paciente_id', '=', 'pae.id')
            ->leftJoin(DB::raw("`{$afDb}`.`personas` as ppac"), 'pae.persona_id', '=', 'ppac.id')
            ->leftJoin(DB::raw("`{$afDb}`.`fi_med_esps` as mesp"), 'r.medicoespecialidad_id', '=', 'mesp.id')
            ->leftJoin(DB::raw("`{$afDb}`.`fi_medicos` as m"), 'mesp.medico_id', '=', 'm.id')
            ->leftJoin(DB::raw("`{$afDb}`.`personas` as pmed"), 'm.persona_id', '=', 'pmed.id')
            ->leftJoin(DB::raw("`{$afDb}`.`fi_subespecialidads` as esp"), 'esp.id', '=', 'mesp.subespecialidad_id')
            ->leftJoin(DB::raw("`{$afDb}`.`fi_modalidads` as fmo"), 'fmo.id', '=', 'esp.modalidad_id')
            ->leftJoin(DB::raw("`{$farmDb}`.`document_types` as tr"), 'r.tipo_receta_id', '=', 'tr.id')
            ->leftJoin(DB::raw("`{$sissuDb}`.`institucions` as i"), 'r.institucion_id', '=', 'i.id')
            ->where('tr.categoria_id', 6)                 // categoría Receta (Farmacia)
            ->where('r.entidad_id', $targetEntityId)
            ->whereIn('r.id', $recetaIds);                // << solo recetas con egresos

        // Filtros opcionales de cabecera
        if ($request->filled('institucion_id'))  $q->where('r.institucion_id', $request->institucion_id);
        if ($request->filled('tipo_receta_id'))  $q->where('r.tipo_receta_id', $request->tipo_receta_id);
        if ($request->filled('paciente')) {
            $term = trim($request->paciente);
            $q->whereRaw("CONCAT_WS(' ', ppac.nombre, ppac.apellido_paterno, ppac.apellido_materno) LIKE ?", ["%{$term}%"]);
        }
        if ($request->filled('receta_id'))       $q->where('r.id', $request->receta_id);

        $recetas = $q->selectRaw("
                r.id,
                r.fecha_emision,
                r.fecha_entrega,
                r.entidad_id,
                tr.descripcion as tipo_receta,
                i.descripcion  as institucion,
                CONCAT_WS(' ', ppac.nombre, ppac.apellido_paterno, ppac.apellido_materno) as paciente,
                TRIM(CONCAT_WS(' ', pmed.nombre, pmed.apellido_paterno, pmed.apellido_materno)) as medico,
                CONCAT_WS(' - ',esp.especialidad,fmo.modalidad) as especialidad
            ")
            ->orderByDesc('r.id')
            ->get();

        // (por seguridad, aunque no debería quedar vacío a esta altura)
        if ($recetas->isEmpty()) {
            return response()->json([]);
        }

        /* 2) Egresos de esas recetas (misma entidad y, si llega, rango por fecha_egreso) */
        $egresos = DB::connection('mysql')
            ->table('discharges as d')
            ->whereIn('d.receta_id', $recetaIds)
            ->where('d.tipo_documento_id', 10)
            ->where('d.entity_id', $targetEntityId)
            ->when($ini && $fin, fn($q) => $q->whereBetween('d.fecha_egreso', [$ini, $fin]))
            ->select('d.id', 'd.receta_id', 'd.numero', 'd.fecha_egreso')
            ->orderBy('d.receta_id')->orderBy('d.numero')
            ->get();

        $egresoIds = $egresos->pluck('id')->all();

        /* 3) Detalles de egreso (devuelto/saldo) */
        $detalles = collect();
        if (!empty($egresoIds)) {
            $detalles = DB::connection('mysql')
                ->table('discharge_details as dd')
                ->join('discharges as d', 'dd.egreso_id', '=', 'd.id')
                ->join('entry_details as ed', 'dd.ingreso_detalle_id', '=', 'ed.id')
                ->join('medicines as m', 'ed.medicamento_id', '=', 'm.id')
                ->leftJoin('pharmaceutical_forms as pf', 'm.formafarmaceutica_id', '=', 'pf.id')
                ->leftJoin('entries as e', 'ed.ingreso_id', '=', 'e.id')
                ->leftJoin('document_types as dt', 'e.tipo_documento_id', '=', 'dt.id')
                ->whereIn('dd.egreso_id', $egresoIds)
                ->selectRaw("
                    dd.id as discharge_detail_id,
                    dd.egreso_id,
                    dd.ingreso_detalle_id,
                    dd.receta_item_id,
                    dd.cantidad_solicitada,
                    dd.costo_unitario,
                    dd.costo_total,
                    ed.lote,
                    ed.fecha_vencimiento,
                    ed.medicamento_id as medicine_id,
                    m.liname,
                    m.nombre_generico as nombre,
                    pf.formafarmaceutica as presentacion,
                    dt.descripcion as tipo_ingreso
                ")
                ->addSelect(DB::raw("
                    (
                        SELECT COALESCE(SUM(re.cantidad),0)
                        FROM entry_details re
                        WHERE re.origen_discharge_detail_id = dd.id
                          AND re.estado_id = 28
                    ) as devuelto
                "))
                ->addSelect(DB::raw("
                    (dd.cantidad_solicitada - (
                        SELECT COALESCE(SUM(re2.cantidad),0)
                        FROM entry_details re2
                        WHERE re2.origen_discharge_detail_id = dd.id
                          AND re2.estado_id = 28
                    )) as saldo
                "))
                ->orderBy('dd.egreso_id')->orderBy('dd.id')
                ->get()
                ->groupBy('egreso_id');
        }

        /* 4) Salida receta -> egresos -> items */
        $egresosPorReceta = $egresos->groupBy('receta_id');

        $out = $recetas->map(function ($r) use ($egresosPorReceta, $detalles) {
            $egs = ($egresosPorReceta->get($r->id) ?? collect())->map(function ($e) use ($detalles) {
                $items = ($detalles->get($e->id) ?? collect())->map(function ($d) {
                    return [
                        'ingreso_detalle_id'  => (int)$d->ingreso_detalle_id,
                        'discharge_detail_id' => (int)$d->discharge_detail_id,
                        'receta_item_id'      => $d->receta_item_id ? (int)$d->receta_item_id : null,
                        'medicine_id'         => (int)$d->medicine_id,
                        'liname'              => $d->liname,
                        'nombre'              => $d->nombre,
                        'presentacion'        => $d->presentacion,
                        'lote'                => $d->lote,
                        'fecha_vencimiento'   => $d->fecha_vencimiento,
                        'tipo_ingreso'        => $d->tipo_ingreso,
                        'cantidad_solicitada' => (float)$d->cantidad_solicitada,
                        'cantidad_entregada'  => (float)$d->cantidad_entregada,
                        'costo_unitario'      => (float)$d->costo_unitario,
                        'costo_total'         => (float)$d->costo_total,
                        'devuelto'            => (float)$d->devuelto,
                        'saldo'               => (float)$d->saldo,
                    ];
                })->values();

                return [
                    'egreso_id'    => (int)$e->id,
                    'numero'       => (int)$e->numero,
                    'fecha_egreso' => $e->fecha_egreso,
                    'items'        => $items,
                ];
            })->values();

            return [
                'receta_id'     => (int)$r->id,
                'entidad_id'    => (int)$r->entidad_id,
                'fecha_emision' => $r->fecha_emision,
                'fecha_entrega' => $r->fecha_entrega,
                'tipo_receta'   => $r->tipo_receta,
                'institucion'   => $r->institucion,
                'paciente'      => $r->paciente,
                'medico'        => $r->medico,
                'especialidad'  => $r->especialidad,
                'egresos'       => $egs,
            ];
        });

        return response()->json($out->values());
    }



    //Egresos por receta
    public function egresosPorReceta(Request $request)
    {


        $request->validate([
            'receta_id'       => ['nullable', 'integer'],
            'inicio'          => ['nullable', 'date'],   // rango por fecha_egreso
            'fin'             => ['nullable', 'date'],
            'emision_inicio'  => ['nullable', 'date'],   // opcional: rango por fecha_emision (receta)
            'emision_fin'     => ['nullable', 'date'],
            'numero'          => ['nullable', 'integer'],
            'entity_id'       => ['nullable', 'integer'],
            'tipo_receta_id'  => ['nullable'],
            'paciente'        => ['nullable', 'string'],
            'medico'          => ['nullable', 'string'],
            'egreso_id'       => ['nullable', 'integer'], // por codigo de egreso
        ]);

        $rawTipo = $request->input('tipo_receta_id'); // ej: [1,2] o "1,2"
        $tipoIds = is_array($rawTipo)
            ? array_values(array_filter(array_map('intval', $rawTipo)))
            : array_values(array_filter(array_map('intval', explode(',', (string)$rawTipo))));

        $sissuDb = config('database.connections.mysql_sissu.database');
        $afDb    = config('database.connections.mysql_afiliacion.database');
        $farmDb  = config('database.connections.mysql.database');

        // entidad: por parámetro o la del usuario
        $entityId = (int) ($request->integer('entity_id') ?: auth()->user()->entity_id);

        /* 1) Recetas (SISSU) ENTREGADAS con filtros de cabecera */
        $qr = DB::connection('mysql_sissu')
            ->table("$sissuDb.recetas as r")
            ->leftJoin(DB::raw("`$afDb`.`persona_afiliado_entidads` as pae"), 'r.paciente_id', '=', 'pae.id')
            ->leftJoin(DB::raw("`$afDb`.`personas` as ppac"), 'pae.persona_id', '=', 'ppac.id')
            ->leftJoin(DB::raw("`$afDb`.`fi_med_esps` as mesp"), 'r.medicoespecialidad_id', '=', 'mesp.id')
            ->leftJoin(DB::raw("`$afDb`.`fi_medicos` as m"), 'mesp.medico_id', '=', 'm.id')
            ->leftJoin(DB::raw("`$afDb`.`personas` as pmed"), 'm.persona_id', '=', 'pmed.id')
            ->leftJoin(DB::raw("`$afDb`.`fi_subespecialidads` as esp"), 'esp.id', '=', 'mesp.subespecialidad_id')
            ->leftJoin(DB::raw("`$afDb`.`fi_modalidads` as fmo"), 'fmo.id', '=', 'esp.modalidad_id')

            ->leftJoin(DB::raw("`$farmDb`.`document_types` as tr"), 'r.tipo_receta_id', '=', 'tr.id')
            ->leftJoin(DB::raw("`$afDb`.`contratos` as c"), 'r.contrato_id', '=', 'c.id')
            ->leftJoin(DB::raw("`$afDb`.`persona_afiliado_entidads` as paet"), 'c.cod_titular', '=', 'paet.id')
            ->leftJoin(DB::raw("`$afDb`.`personas` as ptit"), 'paet.persona_id', '=', 'ptit.id')
            ->leftJoin(DB::raw("`$afDb`.`afiliados` as afipac"), 'pae.afiliado_id', '=', 'afipac.id')
            ->leftJoin(DB::raw("`$afDb`.`afiliados` as afitit"), 'paet.afiliado_id', '=', 'afitit.id')
            ->leftJoin(DB::raw("`$afDb`.`reparticions` as rep"), 'c.reparticion_id', '=', 'rep.id')
            ->leftJoin(DB::raw("`$afDb`.`institucions` as ins"), 'c.institucion_id', '=', 'ins.id')
            ->leftJoin(DB::raw("`$afDb`.`afiliado_tipos` as tipo"), 'c.afiliado_tipo_id', '=', 'tipo.id')
            ->leftJoin(DB::raw("`$sissuDb`.`institucions` as insr"), 'r.institucion_id', '=', 'insr.id')

            ->where('tr.categoria_id', 6);   // tipos de receta (Farmacia)

        $qr->when(!empty($tipoIds), function ($q) use ($tipoIds) {
            $q->whereIn('r.tipo_receta_id', $tipoIds);
        });

        if ($request->filled('paciente')) {
            $term = trim($request->paciente);
            $qr->whereRaw("CONCAT_WS(' ', ppac.nombre, ppac.apellido_paterno, ppac.apellido_materno) LIKE ?", ["%{$term}%"]);
        }
        if ($request->filled('medico')) {
            $term = trim($request->medico);
            $qr->whereRaw("TRIM(CONCAT_WS(' ', pmed.nombre, pmed.apellido_paterno, pmed.apellido_materno)) LIKE ?", ["%{$term}%"]);
        }
        // opcional: rango por fecha_emision de la receta
        if ($request->filled('emision_inicio') && $request->filled('emision_fin')) {
            $qr->whereBetween('r.fecha_emision', [$request->emision_inicio, $request->emision_fin]);
        }

        $recetas = $qr->selectRaw("
            r.id,
            r.fecha_emision,
            r.fecha_entrega,
            r.entidad_id,
            r.paciente_id,
            tr.descripcion as tipo_receta,
            tr.id as tipo_receta_id,
            r.contrato_id,
            CONCAT_WS(' ', ppac.apellido_paterno, ppac.apellido_materno,ppac.nombre) as paciente,
            TRIM(CONCAT_WS(' ', pmed.apellido_paterno, pmed.apellido_materno, pmed.nombre)) as medico,
            CONCAT(esp.especialidad,' - ',fmo.modalidad) as especialidad,
            CONCAT_WS(' ', ptit.apellido_paterno, ptit.apellido_materno, ptit.nombre) as titular,
            c.codigo_referencia,
            rep.reparticion,
            ins.nombre as institucion,
            afipac.matricula as matriculapac,
            afitit.matricula as matriculatit,
            r.diagnostico,
            insr.descripcion as lugar,
            tipo.tipo_afiliado
        ")
            ->orderByDesc('r.id')
            ->get();

        if ($recetas->isEmpty()) {
            return response()->json([], 200);
        }

        $recetasById = $recetas->keyBy('id');
        $recetaIds   = $recetas->pluck('id')->all();

        /* 2) Egresos (Farmacia) de esas recetas, filtrables por numero, fecha_egreso y entidad */
        $qe = DB::connection('mysql')
            ->table('discharges as d')
            ->whereIn('d.receta_id', $recetaIds)
            ->where('d.tipo_documento_id', 10) // Egreso por receta
            ->where('d.entity_id', $entityId);



        if ($request->filled('egreso_id')) {
            $qe->where('d.id', $request->egreso_id);
        }

        if ($request->filled('numero')) {
            $qe->where('d.numero', $request->numero);
        }
        if ($request->filled('inicio') && $request->filled('fin')) {
            // Si vienen fechas desde el frontend, usar esas
            $inicio = Carbon::parse($request->inicio)->startOfDay();
            $fin    = Carbon::parse($request->fin)->endOfDay();
        } else {
            // Si NO vienen, usar fecha actual
            $inicio = Carbon::today()->startOfDay();
            $fin    = Carbon::today()->endOfDay();
        }

        $qe->whereBetween('d.fecha_egreso', [$inicio, $fin]);

        $egresos = $qe->select('d.id', 'd.numero', 'd.fecha_egreso', 'd.receta_id', 'observaciones')
            ->orderBy('d.numero')
            ->get();

        if ($egresos->isEmpty()) {
            return response()->json([], 200);
        }

        $egresoIds = $egresos->pluck('id')->all();

        /* 3) Ítems del egreso + match con ítems de la receta (si dd.receta_item_id) */
        $detallesByEgreso = DB::connection('mysql')
            ->table('discharge_details as dd')
            ->join('discharges as d', 'dd.egreso_id', '=', 'd.id')
            ->join('entry_details as ed', 'dd.ingreso_detalle_id', '=', 'ed.id')
            ->join('medicines as mm', 'ed.medicamento_id', '=', 'mm.id')
            ->leftJoin('pharmaceutical_forms as pf', 'mm.formafarmaceutica_id', '=', 'pf.id')
            ->leftJoin(DB::raw("`$sissuDb`.`receta_medicamentos` as rm"), 'rm.id', '=', 'dd.receta_item_id')
            ->whereIn('dd.egreso_id', $egresoIds)
            ->selectRaw("
            dd.egreso_id,
            dd.receta_item_id,
            mm.liname,
            mm.nombre_generico as nombre,
            mm.categoriamed_id,
            pf.formafarmaceutica as presentacion,
            ed.lote,
            ed.fecha_vencimiento,
            ed.costo_unitario,
            COALESCE(rm.dias, 0) as dias_receta,
            rm.cantidad          as cantidad_receta,
            rm.indicacion        as indicacion_receta,

            dd.cantidad_solicitada,
            dd.cantidad_entregada
        ")
            ->orderBy('dd.egreso_id')
            ->orderBy('dd.id')
            ->get()
            ->groupBy('egreso_id');

        /* 4) Aplanar: una fila por egreso con el header de la receta repetido */
        $out = $egresos->map(function ($e) use ($recetasById, $detallesByEgreso) {
            $h = $recetasById[$e->receta_id];

            $items = ($detallesByEgreso->get($e->id) ?? collect())->map(function ($d) {
                return [
                    'liname'                 => $d->liname,
                    'nombre'                 => $d->nombre,
                    'presentacion'           => $d->presentacion,
                    'categoriamed_id'        => $d->categoriamed_id,
                    'lote'                   => $d->lote,
                    'fecha_vencimiento'     => $d->fecha_vencimiento,
                    'costo_unitario'        => $d->costo_unitario,
                    'dias'                   => (int) $d->dias_receta,
                    'cantidad_receta'        => $d->cantidad_receta ? (float) $d->cantidad_receta : null,
                    'indicacion'             => $d->indicacion_receta,
                    'cantidad_solicitada'    => (float) $d->cantidad_solicitada,
                    'cantidad_entregada'     => (float) $d->cantidad_entregada,
                    'costo_total'            => round(
                        (float) ($d->cantidad_entregada ?? 0) * (float) ($d->costo_unitario ?? 0),
                        2
                    ),
                ];
            })->values();

            return [
                'numero'            => (int) $e->numero,
                'egreso_id'         => (int) $e->id,
                'fecha_egreso'      => $e->fecha_egreso,
                'observaciones'     => $e->observaciones,
                'entidad_id'        => $h->entidad_id,
                'receta_id'         => (int) $h->id,
                'fecha_emision'     => $h->fecha_emision,
                'fecha_entrega'     => $h->fecha_entrega,
                'tipo_receta_id'    => $h->tipo_receta_id,
                'tipo_receta'       => $h->tipo_receta,
                'paciente_id'       => $h->paciente_id,
                'paciente'          => $h->paciente,
                'medico'            => $h->medico,
                'especialidad'      => $h->especialidad,
                'contrato_id'       => $h->contrato_id,
                'titular'           => $h->titular,
                'codigo_referencia' => $h->codigo_referencia,
                'reparticion'       => $h->reparticion,
                'institucion'       => $h->institucion,
                'matriculapac'      => $h->matriculapac,
                'matriculatit'      => $h->matriculatit,
                'diagnostico'       => $h->diagnostico,
                'lugar'             => $h->lugar,
                'tipo_afiliado'     => $h->tipo_afiliado,
                'items'             => $items,
            ];
        });

        return response()->json($out->values(), 200);
    }
    public function medicamentosPorPaciente(Request $request)
    {
        $request->validate([
            'paciente' => 'required|string|min:3',
            'desde'    => 'sometimes|date',
            'hasta'    => 'sometimes|date',
            'entity_id' => 'sometimes|integer',
        ]);

        $sissuDb = config('database.connections.mysql_sissu.database');
        $afDb    = config('database.connections.mysql_afiliacion.database');

        $entityId = (int) ($request->integer('entity_id') ?: auth()->user()->entity_id);
        $term     = trim($request->paciente);

        $desde = $request->filled('desde') ? \Carbon\Carbon::parse($request->desde)->startOfDay() : null;
        $hasta = $request->filled('hasta') ? \Carbon\Carbon::parse($request->hasta)->endOfDay()   : null;

        // 1) Filtramos por egresos de RECETA (tipo_documento_id=10) de la entidad,
        //    enlazando a SISSU para resolver nombre del paciente.
        $rows = DB::connection('mysql')
            ->table('discharges as g')
            ->join('discharge_details as dd', 'dd.egreso_id', '=', 'g.id')
            ->join('entry_details as ed', 'ed.id', '=', 'dd.ingreso_detalle_id')
            ->join('medicines as m', 'm.id', '=', 'ed.medicamento_id')
            ->leftJoin('pharmaceutical_forms as pf', 'pf.id', '=', 'm.formafarmaceutica_id')

            ->leftJoin(DB::raw("`$sissuDb`.`recetas` as r"), 'g.receta_id', '=', 'r.id')
            ->leftJoin(DB::raw("`$sissuDb`.`receta_medicamentos` as rm"), 'rm.id', '=', 'dd.receta_item_id')
            ->leftJoin(DB::raw("`$afDb`.`persona_afiliado_entidads` as pae"), 'r.paciente_id', '=', 'pae.id')
            ->leftJoin(DB::raw("`$afDb`.`personas` as ppac"), 'pae.persona_id', '=', 'ppac.id')
            ->leftJoin(DB::raw("`$afDb`.`fi_med_esps` as mesp"), 'r.medicoespecialidad_id', '=', 'mesp.id')
            ->leftJoin(DB::raw("`$afDb`.`fi_subespecialidads` as esp"), 'esp.id', '=', 'mesp.subespecialidad_id')
            ->leftJoin(DB::raw("`$afDb`.`fi_modalidads` as fmo"), 'fmo.id', '=', 'esp.modalidad_id')
            ->leftJoin(DB::raw("`$afDb`.`fi_medicos` as me"), 'mesp.medico_id', '=', 'me.id')
            ->leftJoin(DB::raw("`$afDb`.`personas` as pmed"), 'me.persona_id', '=', 'pmed.id')

            ->where('g.tipo_documento_id', 10)         // egreso por receta
            ->where('g.entity_id', $entityId)
            ->when($desde && $hasta, fn($q) => $q->whereBetween('g.fecha_egreso', [$desde, $hasta]))
            ->whereRaw("TRIM(CONCAT_WS(' ', ppac.apellido_paterno, ppac.apellido_materno, ppac.nombre)) LIKE ?", ["%{$term}%"])

            ->selectRaw("
            g.id  as egreso_id,
            g.numero,
            g.fecha_egreso,
            r.id  as receta_id,
            r.fecha_emision,
            r.fecha_entrega,

            COALESCE(rm.cantidad_pendiente, 0) as cantidad_pendiente,
            TRIM(CONCAT_WS(' ', ppac.apellido_paterno, ppac.apellido_materno, ppac.nombre)) as paciente,
            TRIM(CONCAT_WS(' ', pmed.apellido_paterno, pmed.apellido_materno, pmed.nombre))  as medico,
            CONCAT(esp.especialidad,' - ',fmo.modalidad) as especialidad,

            m.id as medicine_id,
            m.liname,
            m.nombre_generico as medicamento,
            COALESCE(pf.formafarmaceutica,'') as presentacion,
            ed.lote,
            ed.fecha_vencimiento,

            dd.cantidad_solicitada,
            dd.cantidad_entregada,
            COALESCE(dd.costo_unitario, ed.costo_unitario, 0) as costo_unitario,
            (COALESCE(dd.cantidad_entregada,0)*COALESCE(dd.costo_unitario, ed.costo_unitario, 0)) as costo_total
        ")
            ->orderByDesc('r.id')->orderByDesc('g.fecha_egreso')->orderByDesc('g.numero')->get();

        if ($rows->isEmpty()) {
            return response()->json([
                'meta' => [
                    'paciente'   => $term,
                    'desde'      => optional($desde)->toDateTimeString(),
                    'hasta'      => optional($hasta)->toDateTimeString(),
                    'entity_id'  => $entityId,
                    'generado_en' => now()->format('Y-m-d H:i:s'),
                    'totales'    => ['items' => 0, 'cantidad' => 0, 'valor' => 0.0],
                ],
                'data' => [],
            ]);
        }

        // 2) Estructura: receta -> egreso -> items
        $grouped = $rows->groupBy('receta_id')->map(function ($gByReceta) {
            $firstR = $gByReceta->first();

            $egresos = $gByReceta->groupBy('egreso_id')->map(function ($gByEgreso) {
                $e0 = $gByEgreso->first();
                $items = $gByEgreso->map(function ($x) {
                    return [
                        'medicine_id'        => (int) $x->medicine_id,
                        'liname'             => $x->liname,
                        'medicamento'        => $x->medicamento,
                        'presentacion'       => $x->presentacion,
                        'lote'               => $x->lote,
                        'fecha_vencimiento'  => $x->fecha_vencimiento,
                        'cantidad_solicitada' => (float) $x->cantidad_solicitada,
                        'cantidad_entregada' => (float) $x->cantidad_entregada,
                        'cantidad_pendiente' => (float) $x->cantidad_pendiente,
                        'costo_unitario'     => (float) $x->costo_unitario,
                        'costo_total'        => round((float) $x->costo_total, 2),
                    ];
                })->values();

                return [
                    'egreso_id'    => (int) $e0->egreso_id,
                    'numero'       => (int) $e0->numero,
                    'fecha_egreso' => $e0->fecha_egreso,
                    'items'        => $items,
                    'subtotales'   => [
                        'items'    => $items->count(),
                        'cantidad' => (float) $items->sum('cantidad_entregada'),
                        'valor'    => round((float) $items->sum('costo_total'), 2),
                    ],
                ];
            })->values();

            return [
                'receta_id'     => (int) $firstR->receta_id,
                'fecha_emision' => $firstR->fecha_emision,
                'fecha_entrega' => $firstR->fecha_entrega,
                'paciente'      => $firstR->paciente,
                'medico'        => $firstR->medico,
                'especialidad'  => $firstR->especialidad,
                'egresos'       => $egresos,
                'totales_receta' => [
                    'egresos'  => $egresos->count(),
                    'items'    => (int) $egresos->sum(fn($e) => $e['subtotales']['items']),
                    'cantidad' => (float) $egresos->sum(fn($e) => $e['subtotales']['cantidad']),
                    'valor'    => round((float) $egresos->sum(fn($e) => $e['subtotales']['valor']), 2),
                ],
            ];
        })->values();

        // 3) Totales globales
        $totalItems    = (int) $rows->count();
        $totalCantidad = (float) $rows->sum('cantidad_entregada');
        $totalValor    = round((float) $rows->sum('costo_total'), 2);

        return response()->json([
            'meta' => [
                'paciente'   => $term,
                'desde'      => optional($desde)->toDateTimeString(),
                'hasta'      => optional($hasta)->toDateTimeString(),
                'entity_id'  => $entityId,
                'generado_en' => now()->format('Y-m-d H:i:s'),
                'totales'    => [
                    'items'    => $totalItems,
                    'cantidad' => $totalCantidad,
                    'valor'    => $totalValor,
                ],
            ],
            'data' => $grouped,
        ]);
    }
}
