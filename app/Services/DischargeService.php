<?php

namespace App\Services;

use App\Models\Discharge;
use App\Models\DischargeDetail;
use App\Models\EntryDetail;
use Exception;
use Illuminate\Support\Facades\DB;

class DischargeService
{
    public function store(array $data, int $userId, int $entityId): Discharge
    {
        return DB::transaction(function () use ($data, $userId, $entityId) {
            $tipoDocumentoId = (int) $data['tipo_documento_id'];
            $recetaId = $tipoDocumentoId === 10 ? (int) ($data['receta_id'] ?? 0) : null;

            $numero = Discharge::where('entity_id', $entityId)
                ->where('tipo_documento_id', $tipoDocumentoId)
                ->lockForUpdate()
                ->max('numero') + 1;

            $estadoId = $tipoDocumentoId === 53 ? 27 : 28;
            $discharge = Discharge::create([
                'fecha_egreso' => $data['fecha_egreso'],
                'entity_id' => $entityId,
                'personal_id' => $data['personal_id'] ?? null,
                'tipo_documento_id' => $tipoDocumentoId,
                'numero' => $numero,
                'receta_id' => $recetaId,
                'servicio_id' => $data['servicio_id'] ?? null,
                'proveedor_id' => $data['proveedor_id'] ?? null,
                'observaciones' => $data['observaciones'] ?? '',
                'usr' => $userId,
                'estado_id' => $estadoId,
            ]);

            if ($estadoId === 28) {
                foreach ($data['discharge_details'] as $detail) {
                    $cantidadSolicitada = (int) $detail['cantidad_solicitada'];
                    $costoUnitario = (float) $detail['costo_unitario'];
                    $costoTotal = round($cantidadSolicitada * $costoUnitario, 4);

                    $afectadas = EntryDetail::where('id', $detail['ingreso_detalle_id'])
                        ->where('stock_actual', '>=', $cantidadSolicitada)
                        ->lockForUpdate()
                        ->decrement('stock_actual', $cantidadSolicitada);

                    if ($afectadas === 0) {
                        throw new Exception('Stock insuficiente: egreso concurrente.');
                    }

                    if (round((float) $detail['costo_total'], 4) !== $costoTotal) {
                        throw new Exception('El costo total enviado no coincide con el calculo esperado.');
                    }

                    DischargeDetail::create([
                        'egreso_id' => $discharge->id,
                        'ingreso_detalle_id' => $detail['ingreso_detalle_id'],
                        'receta_item_id' => $detail['receta_item_id'] ?? null,
                        'cantidad_solicitada' => $cantidadSolicitada,
                        'costo_unitario' => $costoUnitario,
                        'costo_total' => $costoTotal,
                        'observaciones' => $detail['observaciones'] ?? '',
                        'usr' => $userId,
                        'estado_id' => 28,
                    ]);
                }
            } else {
                foreach ($data['discharge_details'] as $detail) {
                    $cantidadSolicitada = (int) $detail['cantidad_solicitada'];
                    $costoUnitario = (float) $detail['costo_unitario'];

                    DischargeDetail::create([
                        'egreso_id' => $discharge->id,
                        'ingreso_detalle_id' => $detail['ingreso_detalle_id'],
                        'receta_item_id' => $detail['receta_item_id'] ?? null,
                        'cantidad_solicitada' => $cantidadSolicitada,
                        'costo_unitario' => $costoUnitario,
                        'costo_total' => round($cantidadSolicitada * $costoUnitario, 4),
                        'observaciones' => $detail['observaciones'] ?? '',
                        'usr' => $userId,
                        'estado_id' => 27,
                    ]);
                }
            }

            return $discharge->load([
                'dischargeDetails.entryDetail.medicine',
                'entity',
                'documentType',
                'supplier',
                'estate',
                'user',
                'service',
            ]);
        });
    }

    public function activate(Discharge $discharge, array $data, int $userId): Discharge
    {
        return DB::transaction(function () use ($discharge, $data, $userId) {
            $discharge = Discharge::whereKey($discharge->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $discharge->estado_id !== 27) {
                throw new Exception('Solo se puede activar una solicitud en estado PENDIENTE.');
            }

            $tipoDocumentoId = (int) $data['tipo_documento_id'];

            $discharge->update([
                'fecha_egreso' => $data['fecha_egreso'],
                'entity_id' => $data['entity_id'] ?? $discharge->entity_id,
                'personal_id' => $data['personal_id'] ?? null,
                'tipo_documento_id' => $tipoDocumentoId,
                'servicio_id' => $data['servicio_id'] ?? null,
                'observaciones' => $data['observaciones'] ?? '',
                'estado_id' => 28,
                'usr_mod' => $userId,
                'fhr_mod' => now(),
            ]);

            DischargeDetail::where('egreso_id', $discharge->id)->delete();

            foreach ($data['discharge_details'] as $detail) {
                $cantidadSolicitada = (int) $detail['cantidad_solicitada'];
                $costoUnitario = (float) $detail['costo_unitario'];
                $costoTotal = round($cantidadSolicitada * $costoUnitario, 4);

                $afectadas = EntryDetail::where('id', $detail['ingreso_detalle_id'])
                    ->where('stock_actual', '>=', $cantidadSolicitada)
                    ->lockForUpdate()
                    ->decrement('stock_actual', $cantidadSolicitada);

                if ($afectadas === 0) {
                    throw new Exception('Stock insuficiente para activar la solicitud.');
                }

                if (round((float) $detail['costo_total'], 4) !== $costoTotal) {
                    throw new Exception('El costo total enviado no coincide con el calculo esperado.');
                }

                DischargeDetail::create([
                    'egreso_id' => $discharge->id,
                    'ingreso_detalle_id' => $detail['ingreso_detalle_id'],
                    'receta_item_id' => $detail['receta_item_id'] ?? null,
                    'cantidad_solicitada' => $cantidadSolicitada,
                    'costo_unitario' => $costoUnitario,
                    'costo_total' => $costoTotal,
                    'observaciones' => $detail['observaciones'] ?? '',
                    'usr' => $userId,
                    'estado_id' => 28,
                ]);
            }

            return $discharge->load([
                'dischargeDetails.entryDetail.medicine',
                'entity',
                'documentType',
                'supplier',
                'estate',
                'user',
                'service',
                'personal',
            ]);
        });
    }
}
