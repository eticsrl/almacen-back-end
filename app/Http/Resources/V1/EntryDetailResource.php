<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntryDetailResource extends JsonResource
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
            'ingreso_id'         => $this->ingreso_id,

            'medicamento_id' => $this->medicamento_id,
            'liname' => $this->medicine->liname ?? null,
            'medicamento' => $this->medicine->nombre_generico ?? null,
            'formafarmaceutica' => $this->medicine->pharmaceuticalForm->formafarmaceutica ?? null,
            'lote' => $this->lote,
            'fecha_vencimiento' => $this->fecha_vencimiento,

            'cantidad' => $this->cantidad,
            'costo_unitario' =>(float) $this->costo_unitario,
            'costo_total' =>$this->costo_total,
            'stock_actual' => $this->stock_actual,

            'observaciones' => $this->observaciones,
            'estado_id'=> $this->estate->id ?? null,
            'estado' => $this->estate->descripcion ?? null,

            'usr' => $this->user->id ?? null,
            'usuario' => $this->user->name ?? null,
             //familia
            'item_id' => $this->item_id,

            'receta_item_id'                 => $this->receta_item_id,
            'origen_discharge_detail_id'     => $this->origen_discharge_detail_id,

            // Info resumida del ingreso padre
            'ingreso_origen' => $this->parent ? [
                'id' => $this->parent->id,
                'lote' => $this->parent->lote,
                'fecha_ingreso' => optional($this->parent->entry)->fecha_ingreso,
                'costo_unitario' => $this->parent->costo_unitario,
                'stock_actual' => $this->parent->stock_actual,
            ] : null,
            // Info resumida del egreso-detalle que originó este reingreso (si existe)
            'origen_egreso_detalle' =>$this->origin ? [
                'id'                  => $this->origin->id,
                'egreso_id'           => $this->origin->egreso_id,
                'ingreso_detalle_id'  => $this->origin->ingreso_detalle_id,
                'receta_item_id'      => $this->origin->receta_item_id,
                'costo_unitario'      => $this->origin->costo_unitario,
            ] : null,
        ];
    }
}
