<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Services\DischargeService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class DischargeReportController extends Controller
{

    public function porFecha(Request $req)
{
    $req->validate([
        'desde' => 'required|date',
        'hasta' => 'required|date',
        'group' => 'sometimes|in:detalle,dia,medicamento,documento,servicio,tipo',
        // 'tipo_documento_id' opcional: int | "1,2,3" | [1,2,3]
    ]);

    $desde    = \Carbon\Carbon::parse($req->desde)->startOfDay();
    $hasta    = \Carbon\Carbon::parse($req->hasta)->endOfDay();
    $entityId = $req->input('entity_id');
    $group    = $req->input('group', 'detalle');

    // Normaliza tipo_documento_id a array de ints (o null)
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

    // Base (detalle activo)
    $base = DB::table('discharge_details as gd')
        ->join('discharges as g', 'g.id', '=', 'gd.egreso_id')
        ->join('entry_details as d', 'd.id', '=', 'gd.ingreso_detalle_id')
        ->join('medicines as m', 'm.id', '=', 'd.medicamento_id')
        ->leftJoin('pharmaceutical_forms as pf', 'pf.id', '=', 'm.formafarmaceutica_id')
        // Servicio (cat 8)
        ->leftJoin('document_types as srv', function ($j) {
            $j->on('srv.id', '=', 'g.servicio_id')->where('srv.categoria_id', 8);
        })
        // Tipo de documento del egreso (cat 2)
        ->leftJoin('document_types as tdoc', function ($j) {
            $j->on('tdoc.id', '=', 'g.tipo_documento_id')->where('tdoc.categoria_id', 2);
        })
        ->where('gd.estado_id', 28)
        ->whereBetween('g.fecha_egreso', [$desde, $hasta])
        ->when($entityId, fn($q) => $q->where('g.entity_id', $entityId))
        ->when($tipoDocIds, function ($q) use ($tipoDocIds) {
            return count($tipoDocIds) === 1
                ? $q->where('g.tipo_documento_id', $tipoDocIds[0])
                : $q->whereIn('g.tipo_documento_id', $tipoDocIds);
        });

    // Modos de agrupación
    switch ($group) {
        case 'dia':
            $rows = (clone $base)
                ->selectRaw("
                    DATE(g.fecha_egreso) as fecha,
                    COALESCE(SUM(gd.cantidad_entregada),0) as cantidad,
                    COALESCE(SUM(gd.cantidad_entregada * COALESCE(gd.costo_unitario, d.costo_unitario, 0)),0) as valor
                ")
                ->groupBy(DB::raw('DATE(g.fecha_egreso)'))
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
                    COALESCE(SUM(gd.cantidad_entregada),0) as cantidad,
                    COALESCE(SUM(gd.cantidad_entregada * COALESCE(gd.costo_unitario, d.costo_unitario, 0)),0) as valor
                ")
                ->groupBy('m.id','m.liname','m.nombre_generico','pf.formafarmaceutica')
                ->orderBy('m.liname')
                ->get();
            break;

        case 'documento':
            $rows = (clone $base)
                ->selectRaw("
                    g.id as egreso_id,
                    g.numero as numero,
                    DATE(g.fecha_egreso) as fecha,
                    COALESCE(SUM(gd.cantidad_entregada),0) as cantidad,
                    COALESCE(SUM(gd.cantidad_entregada * COALESCE(gd.costo_unitario, d.costo_unitario, 0)),0) as valor
                ")
                ->groupBy('g.id','g.numero',DB::raw('DATE(g.fecha_egreso)'))
                ->orderBy('fecha')->orderBy('numero')
                ->get();
            break;

        case 'servicio':
            $rows = (clone $base)
                ->selectRaw("
                    g.servicio_id,
                    COALESCE(srv.descripcion,'') as servicio,
                    COALESCE(SUM(gd.cantidad_entregada),0) as cantidad,
                    COALESCE(SUM(gd.cantidad_entregada * COALESCE(gd.costo_unitario, d.costo_unitario, 0)),0) as valor
                ")
                ->groupBy('g.servicio_id','srv.descripcion')
                ->orderBy('servicio')
                ->get();
            break;

        case 'tipo': // <- NUEVA agrupación por tipo de documento
            $rows = (clone $base)
                ->selectRaw("
                    g.tipo_documento_id,
                    COALESCE(tdoc.descripcion,'') as tipo_documento,
                    COALESCE(SUM(gd.cantidad_entregada),0) as cantidad,
                    COALESCE(SUM(gd.cantidad_entregada * COALESCE(gd.costo_unitario, d.costo_unitario, 0)),0) as valor
                ")
                ->groupBy('g.tipo_documento_id','tdoc.descripcion')
                ->orderBy('tipo_documento')
                ->get();
            break;

        default: // detalle
            $rows = (clone $base)
                ->selectRaw("
                    g.id as egreso_id,
                    g.numero as numero,
                    g.fecha_egreso as fecha,
                    COALESCE(tdoc.descripcion,'') as tipo_documento,      -- << visible en detalle
                    COALESCE(srv.descripcion,'') as servicio,
                    m.liname,
                    m.nombre_generico as medicamento,
                    COALESCE(pf.formafarmaceutica,'') as presentacion,
                    d.lote,
                    d.fecha_vencimiento,
                    gd.cantidad_entregada as cantidad,
                    COALESCE(gd.costo_unitario, d.costo_unitario, 0) as costo_unitario,
                    (COALESCE(gd.cantidad_entregada,0) * COALESCE(gd.costo_unitario, d.costo_unitario, 0)) as costo_total
                ")
                ->orderBy('fecha')->orderBy('numero')
                ->get();
    }

    // Totales
    $totalCantidad = (float) $rows->sum('cantidad');
    $totalValor    = (float) ($group === 'detalle' ? $rows->sum('costo_total') : $rows->sum('valor'));

    return response()->json([
        'meta' => [
            'desde'         => $desde->toDateTimeString(),
            'hasta'         => $hasta->toDateTimeString(),
            'entity_id'     => $entityId,
            'group'         => $group,
            'tipo_documento_id' => $tipoDocIds, // para que el frontend sepa qué filtro se aplicó
            'generado_en'   => now()->format('Y-m-d H:i:s'),
            'totales'       => [
                'cantidad' => $totalCantidad,
                'valor'    => round($totalValor, 2),
            ],
        ],
        'data' => $rows,
    ]);
}

}
