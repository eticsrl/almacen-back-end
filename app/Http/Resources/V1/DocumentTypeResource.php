<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentTypeResource extends JsonResource
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
            'categoria_id' => $this->categoria_id,
            'categoria' => $this->category ? $this->category->descripcion : null,
            'descripcion' => $this->descripcion,
            'cod_servicio' => $this->cod_servicio,
            'usr' => $this->usr,
            'usuario'=>$this->user ? $this->user->name : null,
            'estado_id' => $this->estado_id,
        ];
    }
}
