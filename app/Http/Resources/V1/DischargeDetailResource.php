<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DischargeDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'egreso_id'           => $this->egreso_id,
            'ingreso_detalle_id' => $this->ingreso_detalle_id,
            'receta_item_id'      => $this->receta_item_id,

            'liname' => $this->entryDetail->medicine->liname ?? null,
            'medicamento' => $this->entryDetail->medicine->nombre_generico ?? null,
            'formafarmaceutica' => $this->entryDetail->medicine->pharmaceuticalForm->formafarmaceutica ?? null,
            'medicamento_id' => $this->entryDetail->medicine->id ?? null,
            'fecha_vencimiento' => $this->entryDetail->fecha_vencimiento?? null,
            'lote' => $this->entryDetail->lote ?? null,

            'cantidad_solicitada' => $this->cantidad_solicitada,
            'costo_unitario' => $this->costo_unitario,
            'costo_total' => $this->costo_total,

            'observaciones' => $this->observaciones,
            'estado_id'=> $this->estate->id ?? null,
            'estado' => $this->estate->descripcion ?? null,
            'usr' => $this->user->id ?? null,
            'usuario' => $this->user->name ?? null,

                // (Opcional) Métricas de reingresos vinculados a este egreso
                'reingresos_contador' => $this->whenLoaded('returns', fn() => $this->returns->count()),
                'reingresos_cantidad' => $this->whenLoaded('returns', fn() => (int) $this->returns->sum('cantidad')),
        ];
    }
}
