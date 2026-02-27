<?php

namespace App\Services;

use App\Models\Discharge;
use App\Models\DischargeDetail;
use App\Models\EntryDetail;
use Illuminate\Support\Facades\DB;
use App\Models\RecetaMedicamento;
use App\Models\Receta;
use Exception;
use Psr\Log\NullLogger;

class DischargeService
{
    public function store(array $data, int $userId, int $entityId): Discharge
    {
        return DB::transaction(function () use ($data, $userId, $entityId) {
            $tipoDocumentoId = (int) $data['tipo_documento_id'];
            $recetaId = $tipoDocumentoId === 10 ? (int) $data['receta_id'] : null;

            // Generar número correlativo
            $numero = Discharge::where('entity_id', $entityId)
                ->where('tipo_documento_id', $tipoDocumentoId)
                ->lockForUpdate()
                ->max('numero') + 1;

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
                'estado_id' => 28, // ACTIVO
            ]);

            $byItem = []; // Para acumular por receta_item_id (solo si es egreso por receta)

            foreach ($data['discharge_details'] as $detail) {

                $afectadas = EntryDetail::where('id', $detail['ingreso_detalle_id'])
                ->where('stock_actual', '>=', $detail['cantidad_solicitada'])
                ->lockForUpdate()
                ->decrement('stock_actual', $detail['cantidad_solicitada']);

                if ($afectadas === 0) {
                    throw new Exception('Stock insuficiente: egreso concurrente.');
                }

                if (round($detail['costo_total'], 4) !== round($detail['cantidad_solicitada'] * $detail['costo_unitario'], 4)) {
                    throw new Exception('El costo total enviado no coincide con el cálculo esperado.');
                }


                DischargeDetail::create([
                    'egreso_id' => $discharge->id,
                    'ingreso_detalle_id' => $detail['ingreso_detalle_id'],
                    'receta_item_id'      => $detail['receta_item_id'] ?? null,
                    'cantidad_solicitada' => $detail['cantidad_solicitada'],
                    'costo_unitario' => $detail['costo_unitario'],
                    'costo_total' => round($detail['cantidad_solicitada'] * $detail['costo_unitario'], 4),
                    'observaciones' => $detail['observaciones'] ?? '',
                    'usr' => $userId,
                    'estado_id' => 28,
                ]);


                if ($tipoDocumentoId === 10 && !empty($detail['receta_item_id'])) {
                    $byItem[$detail['receta_item_id']] = ($byItem[$detail['receta_item_id']] ?? 0)
                        + (int) $detail['cantidad_solicitada'];
                }
                }

                if ($tipoDocumentoId === 10 && $recetaId && !empty($byItem)) {
                    $this->syncReceta($recetaId, $byItem);
                }

            return $discharge->load([
                'dischargeDetails.entryDetail.medicine',
                'entity',
                'documentType',
                'supplier',
                'estate',
                'user',
                'service'
            ]);
        });
    }
        private function syncReceta(int $recetaId, array $byItem): void
        {
            DB::connection('mysql_sissu')->transaction(function () use ($recetaId, $byItem) {
                foreach ($byItem as $recetaItemId => $aEntregarRaw) {
                    $aEntregar = (int) $aEntregarRaw;
                    if ($aEntregar <= 0) continue;

                    /** @var \App\Models\RecetaMedicamento $item */
                    $item = \App\Models\RecetaMedicamento::on('mysql_sissu')
                        ->where('id', $recetaItemId)
                        ->where('receta_id', $recetaId)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $solicitado   = (int) $item->cantidad;
                    $pendienteOld = (int) ($item->cantidad_pendiente ?? 0);
                    $entregadoPrev= $solicitado - $pendienteOld;

                    if ($entregadoPrev + $aEntregar > $solicitado) {
                        throw new \Exception("Cantidad a entregar supera lo solicitado en el ítem {$item->id}");
                    }

                    //  pendiente nuevo = pendiente actual - a entregar
                    $pendienteNew = max(0, $pendienteOld - $aEntregar);
                    $estadoItem   = $pendienteNew > 0 ? 1 : 2; // 1=PENDIENTE, 2=ENTREGADO

                    // Evita mass assignment: asigna atributo a atributo
                    $item->cantidad_pendiente   = $pendienteNew;
                    $item->estadomedicamento_id = $estadoItem;
                    $item->save();
                }

                $pendientes = \App\Models\RecetaMedicamento::on('mysql_sissu')
                    ->where('receta_id', $recetaId)
                    ->sum('cantidad_pendiente');

                if ((int) $pendientes === 0) {
                    \App\Models\Receta::on('mysql_sissu')
                        ->where('id', $recetaId)
                        ->update([
                            'estado_id'     => 2,
                            'fecha_entrega' => now(),
                        ]);
                }
            });
        }
 }

