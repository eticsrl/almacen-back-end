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
    return response()->json([
        'message' => 'Reporte deshabilitado: la conexion secundaria fue retirada del sistema.'
    ], 410);
}

}
