<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicinePackageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        //return parent::toArray($request);
        return [
            'id' => $this->id,
            'documento_id' => $this->documento_id,
            'paquete' => $this->documento->descripcion ?? null,
            'medicamento_id' => $this->medicamento_id,
            'liname' => $this->medicamento->liname ?? null,
            'nombre_generico' => $this->medicamento->nombre_generico ?? null,
            'cantidad' => $this->cantidad,
            'observaciones' => $this->observaciones,
            'dias' => $this->dias,
            'usr' => $this->usr,
            'usuario' => $this->user->name ?? null,
            'estado_id' => $this->estado_id,
            'estado' =>  $this->estado->descripcion ?? null,
        ];
    }
}
