<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicineResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
       // return parent::toArray($request);
       return[
        'id' => $this->id,
        'liname' => $this->liname,
        'nombre_generico' => $this->nombre_generico,
        'observaciones' => $this->observaciones,
        'formafarmaceutica_id' => $this->formafarmaceutica_id,
        'formafarmaceutica' => $this->pharmaceuticalForm? $this->pharmaceuticalForm->formafarmaceutica : null,
        'categoriamed_id' => $this->categoriamed_id,
        'categoria' => $this->categoria ? $this->categoria->descripcion : null,
        'stockmax' => $this->stockmax,
        'stockmin' => $this->stockmin,
        'darmax' => $this->darmax,
        'darmin' => $this->darmin,
        'usr' => $this->usr,
        'estado_id' => $this->estado_id,


       ];
    }
}
