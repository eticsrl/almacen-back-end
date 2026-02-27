<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DischageMedicinesReportController extends Controller
{
    public function index(Request $req)
    {
        // --- Helpers ---
        $toArr = function ($v) {
            if (is_null($v) || $v === '') return [];
            if (is_array($v)) return array_values(array_filter($v, fn($x)=> $x!==null && $x!==''));
            return array_values(array_filter(array_map('trim', explode(',', (string)$v))));
        };

        // --- Conexiones / alias de BD ---
        $sissuDb = config('database.connections.mysql_sissu.database');
        $afDb    = config('database.connections.mysql_afiliacion.database');
        $farmDb  = config('database.connections.mysql.database');

        // --- Filtros de entrada ---
        $desde    = Carbon::parse($req->input('desde', now()->startOfMonth()))->startOfDay();
        $hasta    = Carbon::parse($req->input('hasta', now()))->endOfDay();
        $entityId = (int) ($req->integer('entity_id') ?: auth()->user()->entity_id);

        $linames  = $toArr($req->input('liname'));             // ej: ["N0105","A0101"]
        $medicos  = array_map('intval', $toArr($req->input('medico_id')));
        $esps     = array_map('intval', $toArr($req->input('especialidad_id')));

        // --- 1) Egresos por receta (Farmacia) + medicamento ---
        $q = DB::connection('mysql')
            ->table('discharge_details as dd')
            ->join('discharges as d', 'dd.egreso_id', '=', 'd.id')
            ->join('entry_details as ed', 'dd.ingreso_detalle_id', '=', 'ed.id')
            ->join('medicines as m', 'ed.medicamento_id', '=', 'm.id')
            ->leftJoin('pharmaceutical_forms as pf', 'pf.id', '=', 'm.formafarmaceutica_id')
            ->where('dd.estado_id', 28)
            ->where('d.tipo_documento_id', 10) // egreso por receta
            ->where('d.entity_id', $entityId)
            ->whereBetween('d.fecha_egreso', [$desde, $hasta])
            ->when($linames, fn($q)=> $q->whereIn('m.liname', $linames))
            ->selectRaw("
                d.id                as egreso_id,
                d.numero            as nro_egreso,
                d.receta_id         as receta_id,
                d.fecha_egreso      as fecha,
                m.liname            as liname,
                m.nombre_generico   as medicamento,
                COALESCE(pf.formafarmaceutica,'') as presentacion,
                dd.cantidad_entregada as cantidad
            ");

        $egresos = $q->orderBy('d.fecha_egreso')->orderBy('d.numero')->get();

        if ($egresos->isEmpty()) {
            return response()->json([
                'meta' => [
                    'desde' => $desde->toDateTimeString(),
                    'hasta' => $hasta->toDateTimeString(),
                    'entity_id' => $entityId,
                    'filtros' => ['liname'=>$linames,'medico_id'=>$medicos,'especialidad_id'=>$esps],
                    'total_registros' => 0,
                    'total_cantidad'  => 0,
                    'generado_en'     => now()->format('Y-m-d H:i:s'),
                ],
                'data' => [],
            ]);
        }

        // --- 2) Traer cabecera de Receta (SISSU): paciente, médico, especialidad ---
        $recetaIds = $egresos->pluck('receta_id')->filter()->unique()->values();
        $qr = DB::connection('mysql_sissu')
            ->table("$sissuDb.recetas as r")
            ->leftJoin(DB::raw("`$afDb`.`persona_afiliado_entidads` as pae"), 'r.paciente_id', '=', 'pae.id')
            ->leftJoin(DB::raw("`$afDb`.`personas` as ppac"), 'pae.persona_id', '=', 'ppac.id')
            ->leftJoin(DB::raw("`$afDb`.`fi_med_esps` as mesp"), 'r.medicoespecialidad_id', '=', 'mesp.id')
            ->leftJoin(DB::raw("`$afDb`.`fi_medicos` as m"), 'mesp.medico_id', '=', 'm.id')
            ->leftJoin(DB::raw("`$afDb`.`personas` as pmed"), 'm.persona_id', '=', 'pmed.id')
            ->leftJoin(DB::raw("`$afDb`.`fi_subespecialidads` as esp"), 'esp.id', '=', 'mesp.subespecialidad_id')
            ->leftJoin(DB::raw("`$afDb`.`fi_modalidads` as fmo"), 'fmo.id', '=', 'esp.modalidad_id')

            ->whereIn('r.id', $recetaIds);

        $recetas = $qr->selectRaw("
                r.id,
                CONCAT_WS(' ', ppac.apellido_paterno, ppac.apellido_materno, ppac.nombre) as paciente,
                m.id as medico_id,
                TRIM(CONCAT_WS(' ', pmed.apellido_paterno, pmed.apellido_materno, pmed.nombre)) as medico,
                esp.id as especialidad_id,
                CONCAT(esp.especialidad,' - ',fmo.modalidad) as especialidad

            ")->get()->keyBy('id');

        // --- 3) Enriquecer & filtrar por médico/especialidad si corresponde ---
        $out = [];
        foreach ($egresos as $e) {
            $rc = $recetas->get($e->receta_id);
            $medId = $rc->medico_id ?? null;
            $espId = $rc->especialidad_id ?? null;

            if (!empty($medicos) && $medId && !in_array($medId, $medicos)) continue;
            if (!empty($esps)    && $espId && !in_array($espId, $esps))     continue;

            $out[] = [
                'nro_egreso'    => (int)$e->nro_egreso,
                'receta_id'     => (int)$e->receta_id,
                'paciente'      => $rc->paciente ?? null,
                'liname'        => $e->liname,
                'medicamento'   => $e->medicamento,
                'presentacion'  => $e->presentacion,
                'cantidad'      => (float)$e->cantidad,
                'medico'        => $rc->medico ?? null,
                'medico_id'     => $medId,
                'especialidad'  => $rc->especialidad ?? null,
                'especialidad_id'=> $espId,
                'fecha'         => $e->fecha,
            ];
        }

        // Totales
        $totalCantidad = array_sum(array_map(fn($r)=> $r['cantidad'], $out));

        return response()->json([
            'meta' => [
                'desde' => $desde->toDateTimeString(),
                'hasta' => $hasta->toDateTimeString(),
                'entity_id' => $entityId,
                'filtros' => [
                    'liname' => $linames,
                    'medico_id' => $medicos,
                    'especialidad_id' => $esps,
                ],
                'total_registros' => count($out),
                'total_cantidad'  => $totalCantidad,
                'generado_en'     => now()->format('Y-m-d H:i:s'),
            ],
            'data' => array_values($out),
        ]);
    }
}
