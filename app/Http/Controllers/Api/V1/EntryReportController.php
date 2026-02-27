<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EntryReportController extends Controller
{

    public function porFecha(Request $req)
    {
        $req->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date',
            'group' => 'sometimes|in:detalle,dia,medicamento,documento,proveedor,tipo',
        ]);

        $desde    = Carbon::parse($req->desde)->startOfDay();
        $hasta    = Carbon::parse($req->hasta)->endOfDay();
        $entityId = $req->input('entity_id');
        $group    = $req->input('group', 'detalle');

        // Normaliza tipo_documento_id a array (cat=1 para Ingresos)
        $tipoDocInput = $req->input('tipo_documento_id');
        $tipoDocIds = null;
        if (!is_null($tipoDocInput)) {
            if (is_array($tipoDocInput)) {
                $tipoDocIds = array_values(array_filter(array_map('intval', $tipoDocInput), fn($v)=>$v>0));
            } else {
                $tipoDocIds = array_values(array_filter(array_map('intval', explode(',', (string)$tipoDocInput)), fn($v)=>$v>0));
            }
            if (!$tipoDocIds) $tipoDocIds = null;
        }

        // Base: detalle ACTIVO (d.estado_id = 28)
        $base = DB::table('entry_details as d')
            ->join('entries as e', 'e.id', '=', 'd.ingreso_id')
            ->join('medicines as m', 'm.id', '=', 'd.medicamento_id')
            ->leftJoin('pharmaceutical_forms as pf', 'pf.id', '=', 'm.formafarmaceutica_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'e.proveedor_id')
            // Tipo de documento del ingreso (cat=1)
            ->leftJoin('document_types as tdoc', function ($j) {
                $j->on('tdoc.id', '=', 'e.tipo_documento_id')->where('tdoc.categoria_id', 1);
            })
            ->where('d.estado_id', 28)
            ->whereBetween('e.fecha_ingreso', [$desde, $hasta])
            ->when($entityId, fn($q) => $q->where('e.entity_id', $entityId))
            ->when($tipoDocIds, function ($q) use ($tipoDocIds) {
                return count($tipoDocIds) === 1
                    ? $q->where('e.tipo_documento_id', $tipoDocIds[0])
                    : $q->whereIn('e.tipo_documento_id', $tipoDocIds);
            });

        switch ($group) {
            case 'dia':
                $rows = (clone $base)
                    ->selectRaw("
                        DATE(e.fecha_ingreso) as fecha,
                        COALESCE(SUM(d.cantidad),0) as cantidad,
                        COALESCE(SUM(d.cantidad * COALESCE(d.costo_unitario,0)),0) as valor
                    ")
                    ->groupBy(DB::raw('DATE(e.fecha_ingreso)'))
                    ->orderBy('fecha')
                    ->get();
                break;

            case 'medicamento':
                $rows = (clone $base)
                    ->selectRaw("
                        m.id as medicamento_id,
                        m.liname,
                        m.nombre_generico as medicamento,
                        COALESCE(pf.formafarmaceutica,'') as presentacion,
                        COALESCE(SUM(d.cantidad),0) as cantidad,
                        COALESCE(SUM(d.cantidad * COALESCE(d.costo_unitario,0)),0) as valor
                    ")
                    ->groupBy('m.id','m.liname','m.nombre_generico','pf.formafarmaceutica')
                    ->orderBy('m.liname')
                    ->get();
                break;

            case 'documento':
                $rows = (clone $base)
                    ->selectRaw("
                        e.id as ingreso_id,
                        e.numero as numero,
                        DATE(e.fecha_ingreso) as fecha,
                        COALESCE(SUM(d.cantidad),0) as cantidad,
                        COALESCE(SUM(d.cantidad * COALESCE(d.costo_unitario,0)),0) as valor
                    ")
                    ->groupBy('e.id','e.numero',DB::raw('DATE(e.fecha_ingreso)'))
                    ->orderBy('fecha')->orderBy('numero')
                    ->get();
                break;

            case 'proveedor':
                $rows = (clone $base)
                    ->selectRaw("
                        e.proveedor_id,
                        COALESCE(s.nombre, '') as proveedor,
                        COALESCE(SUM(d.cantidad),0) as cantidad,
                        COALESCE(SUM(d.cantidad * COALESCE(d.costo_unitario,0)),0) as valor
                    ")
                    ->groupBy('e.proveedor_id','s.razon_social','s.nombre')
                    ->orderBy('proveedor')
                    ->get();
                break;

            case 'tipo':
                $rows = (clone $base)
                    ->selectRaw("
                        e.tipo_documento_id,
                        COALESCE(tdoc.descripcion,'') as tipo_documento,
                        COALESCE(SUM(d.cantidad),0) as cantidad,
                        COALESCE(SUM(d.cantidad * COALESCE(d.costo_unitario,0)),0) as valor
                    ")
                    ->groupBy('e.tipo_documento_id','tdoc.descripcion')
                    ->orderBy('tipo_documento')
                    ->get();
                break;

            default: // detalle
                $rows = (clone $base)
                    ->selectRaw("
                        e.id as ingreso_id,
                        e.numero as numero,
                        e.fecha_ingreso as fecha,
                        COALESCE(tdoc.descripcion,'') as tipo_documento,
                        COALESCE( s.nombre, '') as proveedor,
                        m.liname,
                        m.nombre_generico as medicamento,
                        COALESCE(pf.formafarmaceutica,'') as presentacion,
                        d.lote,
                        d.fecha_vencimiento,
                        d.cantidad as cantidad,
                        COALESCE(d.costo_unitario,0) as costo_unitario,
                        (COALESCE(d.cantidad,0) * COALESCE(d.costo_unitario,0)) as costo_total
                    ")
                    ->orderBy('fecha')->orderBy('numero')
                    ->get();
        }

        $totalCantidad = (float) $rows->sum('cantidad');
        $totalValor    = (float) ($group === 'detalle' ? $rows->sum('costo_total') : $rows->sum('valor'));

        return response()->json([
            'meta' => [
                'desde'             => $desde->toDateTimeString(),
                'hasta'             => $hasta->toDateTimeString(),
                'entity_id'         => $entityId,
                'group'             => $group,
                'tipo_documento_id' => $tipoDocIds,
                'generado_en'       => now()->format('Y-m-d H:i:s'),
                'totales'           => [
                    'cantidad' => $totalCantidad,
                    'valor'    => round($totalValor, 2),
                ],
            ],
            'data' => $rows,
        ]);
    }

    // App/Http/Controllers/Api/V1/EntryController.php


public function reingresosPorReceta(Request $request)
{
    $request->validate([
        'inicio'          => ['nullable','date'],   // rango por fecha_ingreso (reingreso)
        'fin'             => ['nullable','date'],
        'numero_reingreso'=> ['nullable','integer'],
        'numero_egreso'   => ['nullable','integer'],
        'receta_id'       => ['nullable','integer'],
        'tipo_receta_id'  => ['nullable'],          // admite array o "1,2,3"
        'paciente'        => ['nullable','string'],
        'medico'          => ['nullable','string'],
        'entity_id'       => ['nullable','integer'],
    ]);

    // Parse multi-tipo (array o "1,2")
    $rawTipo = $request->input('tipo_receta_id');
    $tipoIds = is_array($rawTipo)
        ? array_values(array_filter(array_map('intval', $rawTipo)))
        : array_values(array_filter(array_map('intval', explode(',', (string)$rawTipo))));

    $sissuDb = config('database.connections.mysql_sissu.database');
    $afDb    = config('database.connections.mysql_afiliacion.database');
    $farmDb  = config('database.connections.mysql.database');

    $entityId = (int) ($request->integer('entity_id') ?: auth()->user()->entity_id);

    // Rango de fechas (por fecha_ingreso del reingreso)
    if ($request->filled('inicio') && $request->filled('fin')) {
        $fini = Carbon::parse($request->inicio)->startOfDay();
        $ffin = Carbon::parse($request->fin)->endOfDay();
    } else {
        $fini = Carbon::today()->startOfDay();
        $ffin = Carbon::today()->endOfDay();
    }

    // Base: detalles de reingreso que provienen de un egreso por receta
    $q = DB::connection('mysql')
        ->table('entry_details as ed')
        ->join('entries as e', 'ed.ingreso_id', '=', 'e.id')                               // reingreso
        ->join('discharge_details as dd', 'ed.origen_discharge_detail_id', '=', 'dd.id')   // detalle egreso original
        ->join('discharges as d', 'dd.egreso_id', '=', 'd.id')                             // egreso original
        ->leftJoin('medicines as mm', 'ed.medicamento_id', '=', 'mm.id')
        ->leftJoin('pharmaceutical_forms as pf', 'mm.formafarmaceutica_id', '=', 'pf.id')
        // receta y personas (SISSU/AF)
        ->leftJoin(DB::raw("`$sissuDb`.`recetas` as r"), 'd.receta_id', '=', 'r.id')
        ->leftJoin(DB::raw("`$farmDb`.`document_types` as tr"), 'r.tipo_receta_id', '=', 'tr.id')
        ->leftJoin(DB::raw("`$afDb`.`persona_afiliado_entidads` as pae"), 'r.paciente_id', '=', 'pae.id')
        ->leftJoin(DB::raw("`$afDb`.`personas` as ppac"), 'pae.persona_id', '=', 'ppac.id')
        ->leftJoin(DB::raw("`$afDb`.`fi_med_esps` as mesp"), 'r.medicoespecialidad_id', '=', 'mesp.id')
        ->leftJoin(DB::raw("`$afDb`.`fi_medicos` as m"), 'mesp.medico_id', '=', 'm.id')
        ->leftJoin(DB::raw("`$afDb`.`personas` as pmed"), 'm.persona_id', '=', 'pmed.id')
        ->leftJoin(DB::raw("`$afDb`.`fi_subespecialidads` as esp"), 'esp.id', '=', 'mesp.subespecialidad_id')
        ->leftJoin(DB::raw("`$afDb`.`fi_modalidads` as fmo"), 'fmo.id', '=', 'esp.modalidad_id')
        // condiciones
        ->whereNotNull('ed.origen_discharge_detail_id')
        ->where('e.entity_id', $entityId)
        ->where('d.entity_id', $entityId)
        ->where('e.tipo_documento_id', 6)   // Reingreso por devolución
        ->where('d.tipo_documento_id', 10)  // Egreso por receta
        ->whereBetween('e.fecha_ingreso', [$fini, $ffin]);

    // Filtros
    if (!empty($tipoIds)) {
        $q->whereIn('r.tipo_receta_id', $tipoIds);
    }
    if ($request->filled('numero_reingreso')) {
        $q->where('e.numero', $request->numero_reingreso);
    }
    if ($request->filled('numero_egreso')) {
        $q->where('d.numero', $request->numero_egreso);
    }
    if ($request->filled('receta_id')) {
        $q->where('r.id', $request->receta_id);
    }
    if ($request->filled('paciente')) {
        $term = trim($request->paciente);
        $q->whereRaw("CONCAT_WS(' ', ppac.nombre, ppac.apellido_paterno, ppac.apellido_materno) LIKE ?", ["%{$term}%"]);
    }
    if ($request->filled('medico')) {
        $term = trim($request->medico);
        $q->whereRaw("TRIM(CONCAT_WS(' ', pmed.apellido_paterno, pmed.apellido_materno, pmed.nombre)) LIKE ?", ["%{$term}%"]);
    }

    $rows = $q->selectRaw("
            e.id   as reingreso_id,
            e.numero as numero_reingreso,
            e.fecha_ingreso,
            e.observaciones as observaciones_reingreso,


            d.id   as egreso_id,
            d.numero as numero_egreso,
            d.fecha_egreso,


            r.id as receta_id,
            tr.descripcion as tipo_receta,

            TRIM(CONCAT_WS(' ', ppac.apellido_paterno, ppac.apellido_materno, ppac.nombre)) as paciente,
            TRIM(CONCAT_WS(' ', pmed.apellido_paterno, pmed.apellido_materno, pmed.nombre)) as medico,
            CONCAT(esp.especialidad,' - ',fmo.modalidad) as especialidad,

            mm.liname,
            mm.nombre_generico as nombre,
            pf.formafarmaceutica as presentacion,
            ed.lote,
            ed.fecha_vencimiento,
            ed.costo_unitario,
            dd.cantidad_entregada,
            ed.cantidad as cantidad_devuelta,
            ROUND(ed.cantidad * ed.costo_unitario, 2) as costo_total,
            ed.observaciones
        ")
        ->orderBy('e.numero')
        ->orderBy('ed.id')
        ->get();

    if ($rows->isEmpty()) {
        return response()->json([], 200);
    }

    // Agrupar por reingreso (una fila por reingreso con items)
    $out = $rows->groupBy('reingreso_id')->map(function ($g) {
        $h = $g->first();
        return [
            'reingreso_id'     => (int) $h->reingreso_id,
            'numero_reingreso' => (int) $h->numero_reingreso,
            'fecha_ingreso'    => $h->fecha_ingreso,
            'observaciones_reingreso'    => $h->observaciones_reingreso,


            'egreso_id'        => (int) $h->egreso_id,
            'numero_egreso'    => (int) $h->numero_egreso,
            'fecha_egreso'     => $h->fecha_egreso,

            'receta_id'        => (int) $h->receta_id,
            'tipo_receta'      => $h->tipo_receta,

            'paciente'         => $h->paciente,
            'medico'           => $h->medico,
            'especialidad'     => $h->especialidad,

            'items' => $g->map(function ($r) {
                return [
                    'liname'            => $r->liname,
                    'nombre'            => $r->nombre,
                    'presentacion'      => $r->presentacion,
                    'lote'              => $r->lote,
                    'fecha_vencimiento' => $r->fecha_vencimiento,
                    'cantidad_egresada' => (float) $r->cantidad_entregada,
                    'cantidad_reingresada'  => (float) $r->cantidad_devuelta,
                    'costo_unitario'    => (float) $r->costo_unitario,
                    'costo_total'       => (float) $r->costo_total,
                    'observaciones'     => $r->observaciones,
                ];
            })->values(),
        ];
    })->values();

    return response()->json($out, 200);
}

}
