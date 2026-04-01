<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Services\DischargeService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class InventarioResumenController extends Controller
{
    public function index(Request $req)
    {
        // Fechas incluyentes
        $desde = Carbon::parse($req->input('desde', now()->startOfMonth()))->startOfDay();
        $hasta = Carbon::parse($req->input('hasta', now()))->endOfDay();
        $entityId = $req->input('entity_id'); // opcional
        $desdeStr = $desde->toDateTimeString();   // 'YYYY-MM-DD HH:MM:SS'
        $hastaStr = $hasta->toDateTimeString();

        // --- INGRESOS (detalle activo) ---
        $ing = DB::table('entry_details as d')
            ->join('entries as e', 'e.id', '=', 'd.ingreso_id')
            ->selectRaw("
                d.medicamento_id,
                e.fecha_ingreso AS fecha,
                d.cantidad      AS qty,
                COALESCE(d.costo_unitario,0) AS unit_cost,
                COALESCE(d.cantidad,0) * COALESCE(d.costo_unitario,0) AS val,
                1 AS signo
            ")
            ->where('d.estado_id', 28); // << estado a nivel detalle
        if ($entityId) $ing->where('e.entity_id', $entityId);

        // --- EGRESOS (detalle activo) ---
        $egr = DB::table('discharge_details as gd')
            ->join('discharges as g', 'g.id', '=', 'gd.egreso_id')
            ->join('entry_details as d', 'd.id', '=', 'gd.ingreso_detalle_id') // para medicamento_id
            ->selectRaw("
                d.medicamento_id,
                g.fecha_egreso   AS fecha,
                gd.cantidad_entregada AS qty,
                COALESCE(gd.costo_unitario,0) AS unit_cost,
                COALESCE(gd.cantidad_entregada,0) * COALESCE(gd.costo_unitario,0) AS val,
                -1 AS signo
            ")
            ->where('gd.estado_id', 28); // << estado a nivel detalle
        if ($entityId) $egr->where('g.entity_id', $entityId);

        // Unión
        $movs = $ing->unionAll($egr);

        // Agregado por medicamento + presentación
        $rows = DB::query()->fromSub($movs, 'm')
    ->join('medicines as med', 'med.id', '=', 'm.medicamento_id')
    ->leftJoin('pharmaceutical_forms as pf', 'pf.id', '=', 'med.formafarmaceutica_id')
    ->selectRaw("
        m.medicamento_id,
        med.liname,
        med.nombre_generico AS descripcion,
        COALESCE(pf.formafarmaceutica,'') AS presentacion,

        SUM(CASE WHEN m.fecha <  '{$desdeStr}' THEN m.qty * m.signo ELSE 0 END) AS saldo_ini_cantidad,
        SUM(CASE WHEN m.fecha <  '{$desdeStr}' THEN m.val * m.signo ELSE 0 END) AS saldo_ini_valor,

        SUM(CASE WHEN m.fecha >= '{$desdeStr}' AND m.fecha <= '{$hastaStr}' AND m.signo =  1 THEN m.qty ELSE 0 END) AS ingresos_cantidad,
        SUM(CASE WHEN m.fecha >= '{$desdeStr}' AND m.fecha <= '{$hastaStr}' AND m.signo =  1 THEN m.val ELSE 0 END) AS ingresos_valor,

        SUM(CASE WHEN m.fecha >= '{$desdeStr}' AND m.fecha <= '{$hastaStr}' AND m.signo = -1 THEN m.qty ELSE 0 END) AS egresos_cantidad,
        SUM(CASE WHEN m.fecha >= '{$desdeStr}' AND m.fecha <= '{$hastaStr}' AND m.signo = -1 THEN m.val ELSE 0 END) AS egresos_valor,

        SUM(CASE WHEN m.fecha <= '{$hastaStr}' THEN m.qty * m.signo ELSE 0 END) AS saldo_fin_cantidad,
        SUM(CASE WHEN m.fecha <= '{$hastaStr}' THEN m.val * m.signo ELSE 0 END) AS saldo_fin_valor
    ")
    ->groupBy('m.medicamento_id', 'med.liname', 'med.nombre_generico', 'pf.formafarmaceutica')
    ->havingRaw("(saldo_ini_cantidad <> 0 OR ingresos_cantidad <> 0 OR egresos_cantidad <> 0 OR saldo_fin_cantidad <> 0)")
    ->orderBy('med.liname')
    ->get();

        // Totales
        $totales = [
            'saldo_ini_cantidad' => 0, 'saldo_ini_valor' => 0,
            'ingresos_cantidad'  => 0, 'ingresos_valor'  => 0,
            'egresos_cantidad'   => 0, 'egresos_valor'   => 0,
            'saldo_fin_cantidad' => 0, 'saldo_fin_valor' => 0,
        ];
        foreach ($rows as $r) foreach ($totales as $k => $v) $totales[$k] += (float) $r->$k;

        return response()->json([
            'meta' => [
                'desde'       => $desde->toDateTimeString(),
                'hasta'       => $hasta->toDateTimeString(),
                'entity_id'   => $entityId,
                'generado_en' => now()->format('Y-m-d H:i:s'),
                'totales'     => $totales,
            ],
            'data' => $rows,
        ]);
    }
    /*public function movimientos(Request $req)
    {
        $req->validate([
            'medicamento_id' => 'required|integer',
            'desde' => 'required|date',
            'hasta' => 'required|date',
        ]);

        $medId    = (int) $req->medicamento_id;
        $desde    = \Carbon\Carbon::parse($req->desde)->startOfDay();
        $hasta    = \Carbon\Carbon::parse($req->hasta)->endOfDay();
        $entityId = $req->input('entity_id');

        // SALDO INICIAL (ingresos - egresos antes del DESDE)
        $ingPrev = DB::table('entry_details as d')
            ->join('entries as e','e.id','=','d.ingreso_id')
            ->join ('document_types as dt','dt.id','=','e.tipo_documento_id')
            ->where('d.estado_id',28)
            ->where('d.medicamento_id',$medId)
            ->when($entityId, fn($q)=>$q->where('e.entity_id',$entityId))
            ->where('e.fecha_ingreso','<',$desde)
            ->selectRaw('COALESCE(SUM(d.cantidad),0) as cant, COALESCE(SUM(d.cantidad*d.costo_unitario),0) as val')
            ->first();

        $egrPrev = DB::table('discharge_details as gd')
            ->join('discharges as g','g.id','=','gd.egreso_id')
            ->join('entry_details as d','d.id','=','gd.ingreso_detalle_id')
            ->where('gd.estado_id',28)
            ->where('d.medicamento_id',$medId)
            ->when($entityId, fn($q)=>$q->where('g.entity_id',$entityId))
            ->where('g.fecha_egreso','<',$desde)
            ->selectRaw('COALESCE(SUM(gd.cantidad_entregada),0) as cant, COALESCE(SUM(gd.cantidad_entregada*gd.costo_unitario),0) as val')
            ->first();

        $saldoCant = ($ingPrev->cant ?? 0) - ($egrPrev->cant ?? 0);
        $saldoVal  = ($ingPrev->val  ?? 0) - ($egrPrev->val  ?? 0);

        // QUERY DE INGRESOS EN EL PERÍODO
        $ing = DB::table('entry_details as d')
            ->join('entries as e','e.id','=','d.ingreso_id')
            ->where('d.estado_id',28)
            ->where('d.medicamento_id',$medId)
            ->when($entityId, fn($q)=>$q->where('e.entity_id',$entityId))
            ->whereBetween('e.fecha_ingreso', [$desde, $hasta])
            ->selectRaw("
            e.fecha_ingreso as fecha,
            'INGRESO' as tipo,
            e.numero as doc_num,
            e.tipo_documento_id,
            e.observaciones,
            d.lote as lote,
            d.fecha_vencimiento as fecha_venc,
            d.cantidad as qty,
            COALESCE(d.costo_unitario,0) as unit_cost,
            (COALESCE(d.cantidad,0)*COALESCE(d.costo_unitario,0)) as val
        ");

        // QUERY DE EGRESOS EN EL PERÍODO
        $egr = DB::table('discharge_details as gd')
            ->join('discharges as g','g.id','=','gd.egreso_id')
            ->join('entry_details as d','d.id','=','gd.ingreso_detalle_id')
            ->where('gd.estado_id',28)
            ->where('d.medicamento_id',$medId)
            ->when($entityId, fn($q)=>$q->where('g.entity_id',$entityId))
            ->whereBetween('g.fecha_egreso', [$desde, $hasta])
            ->selectRaw("
                g.fecha_egreso as fecha,
                'EGRESO'  as tipo,
                g.numero as doc_num,
                g.tipo_documento_id,
                g.observaciones,
                d.lote as lote,
                d.fecha_vencimiento as fecha_venc,
                gd.cantidad_entregada as qty,
                COALESCE(gd.costo_unitario, d.costo_unitario, 0) as unit_cost,
                (COALESCE(gd.cantidad_entregada,0)*COALESCE(gd.costo_unitario, d.costo_unitario, 0)) as val
            ");
        // UNION + ORDEN
        $union = $ing->unionAll($egr);
        $movs  = DB::query()
            ->fromSub($union, 't')
            ->orderBy('fecha')
            ->get();

        // SALDO ACUMULADO
        foreach ($movs as $m) {
            if ($m->tipo === 'INGRESO') {
                $saldoCant += $m->qty;
                $saldoVal  += $m->val;
            } else {
                $saldoCant -= $m->qty;
                $saldoVal  -= $m->val;
            }
            $m->saldo_cantidad = $saldoCant;
            $m->saldo_valor    = round($saldoVal, 2);
        }

        return response()->json([
            'meta' => [
                'desde' => $desde->toDateTimeString(),
                'hasta' => $hasta->toDateTimeString(),
                'entity_id' => $entityId,
                'saldo_inicial' => [
                    'cantidad' => ($ingPrev->cant ?? 0) - ($egrPrev->cant ?? 0),
                    'valor'    => round((($ingPrev->val ?? 0) - ($egrPrev->val ?? 0)), 2),
                ],
            ],
            'data' => $movs,
        ]);
    }*/
    public function movimientos(Request $req)
    {
        $req->validate([
            'medicamento_id' => 'required|integer',
            'desde'          => 'required|date',
            'hasta'          => 'required|date',
        ]);

        $medId    = (int) $req->medicamento_id;
        $desde    = \Carbon\Carbon::parse($req->desde)->startOfDay();
        $hasta    = \Carbon\Carbon::parse($req->hasta)->endOfDay();
        $entityId = $req->input('entity_id');

        /* -------- SALDO INICIAL -------- */
        $ingPrev = DB::table('entry_details as d')
            ->join('entries as e','e.id','=','d.ingreso_id')
            ->where('d.estado_id',28)
            ->where('d.medicamento_id',$medId)
            ->when($entityId, fn($q)=>$q->where('e.entity_id',$entityId))
            ->where('e.fecha_ingreso','<',$desde)
            ->selectRaw('COALESCE(SUM(d.cantidad),0) as cant, COALESCE(SUM(d.cantidad*d.costo_unitario),0) as val')
            ->first();

        $egrPrev = DB::table('discharge_details as gd')
            ->join('discharges as g','g.id','=','gd.egreso_id')
            ->join('entry_details as d','d.id','=','gd.ingreso_detalle_id')
            ->where('gd.estado_id',28)
            ->where('d.medicamento_id',$medId)
            ->when($entityId, fn($q)=>$q->where('g.entity_id',$entityId))
            ->where('g.fecha_egreso','<',$desde)
            ->selectRaw('COALESCE(SUM(gd.cantidad_entregada),0) as cant, COALESCE(SUM(gd.cantidad_entregada*gd.costo_unitario),0) as val')
            ->first();

        $saldoCant = ($ingPrev->cant ?? 0) - ($egrPrev->cant ?? 0);
        $saldoVal  = ($ingPrev->val  ?? 0) - ($egrPrev->val  ?? 0);

        /* -------- INGRESOS EN EL PERÍODO -------- */
        $ing = DB::table('entry_details as d')
            ->join('entries as e','e.id','=','d.ingreso_id')
            ->leftJoin('document_types as tdi', 'tdi.id', '=', 'e.tipo_documento_id') // tipo ingreso
            ->where('d.medicamento_id',$medId)
            ->when($entityId, fn($q)=>$q->where('e.entity_id',$entityId))
            ->whereBetween('e.fecha_ingreso', [$desde, $hasta])
            ->selectRaw("
                e.fecha_ingreso                       as fecha,
                'INGRESO'                             as mov,
                e.numero                              as doc_num,
                e.tipo_documento_id                   as doc_tipo_id,
                COALESCE(tdi.descripcion,'')          as doc_tipo,
                COALESCE(e.observaciones,'')          as obs_calc,
                d.lote                                as lote,
                d.fecha_vencimiento                   as fecha_venc,
                d.cantidad                            as qty,
                COALESCE(d.costo_unitario,0)          as unit_cost,
                (COALESCE(d.cantidad,0)*COALESCE(d.costo_unitario,0)) as val,
                NULL                                  as receta_id,
                ''                                    as paciente,
                ''                                    as medico,
                ''                                    as especialidad
            ");

        /* -------- EGRESOS EN EL PERÍODO -------- */
        $egr = DB::table('discharge_details as gd')
            ->join('discharges as g','g.id','=','gd.egreso_id')
            ->join('entry_details as d','d.id','=','gd.ingreso_detalle_id')
            ->leftJoin('document_types as tdg', 'tdg.id', '=', 'g.tipo_documento_id') // tipo egreso

            ->where('gd.estado_id',28)
            ->where('d.medicamento_id',$medId)
            ->when($entityId, fn($q)=>$q->where('g.entity_id',$entityId))
            ->whereBetween('g.fecha_egreso', [$desde, $hasta])

            ->selectRaw("
                g.fecha_egreso                        as fecha,
                'EGRESO'                              as mov,
                g.numero                              as doc_num,
                g.tipo_documento_id                   as doc_tipo_id,
                COALESCE(tdg.descripcion,'')          as doc_tipo,

                COALESCE(g.observaciones,'')          as obs_calc,

                d.lote                                as lote,
                d.fecha_vencimiento                   as fecha_venc,
                gd.cantidad_entregada                 as qty,
                COALESCE(gd.costo_unitario, d.costo_unitario, 0) as unit_cost,
                (COALESCE(gd.cantidad_entregada,0)*COALESCE(gd.costo_unitario, d.costo_unitario, 0)) as val,

                g.receta_id                           as receta_id,
                TRIM(CONCAT_WS(' ', ppac.apellido_paterno, ppac.apellido_materno, ppac.nombre)) as paciente,
                TRIM(CONCAT_WS(' ', pmed.apellido_paterno, pmed.apellido_materno, pmed.nombre))  as medico,
                COALESCE(CONCAT(esp.especialidad, CASE WHEN fmo.modalidad IS NOT NULL THEN CONCAT(' - ', fmo.modalidad) ELSE '' END), '') as especialidad
            ");

        /* -------- UNION + ORDEN -------- */
        $union = $ing->unionAll($egr);
        $movs  = DB::query()
            ->fromSub($union, 't')
            ->orderBy('fecha')
            ->get();

        /* -------- SALDO ACUMULADO -------- */
        foreach ($movs as $m) {
            if ($m->mov === 'INGRESO') {
                $saldoCant += $m->qty;
                $saldoVal  += $m->val;
            } else {
                $saldoCant -= $m->qty;
                $saldoVal  -= $m->val;
            }
            $m->saldo_cantidad = $saldoCant;
            $m->saldo_valor    = round($saldoVal, 2);
        }

        return response()->json([
            'meta' => [
                'desde' => $desde->toDateTimeString(),
                'hasta' => $hasta->toDateTimeString(),
                'entity_id' => $entityId,
                'saldo_inicial' => [
                    'cantidad' => ($ingPrev->cant ?? 0) - ($egrPrev->cant ?? 0),
                    'valor'    => round((($ingPrev->val ?? 0) - ($egrPrev->val ?? 0)), 2),
                ],
            ],
            'data' => $movs,
        ]);
    }




}
