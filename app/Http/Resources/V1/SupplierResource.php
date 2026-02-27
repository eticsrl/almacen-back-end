<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
       // return parent::toArray($request);
       return [
        'id'=>$this->id,
        'nombre'=>$this->nombre,
        'nit'=>$this->nit,
        'direccion'=>$this->direccion,
        'telefono'=>$this->telefono,
        'persona_contacto'=>$this->persona_contacto,
        'celular'=>$this->celular,
        'email'=>$this->email,
        'observaciones'=>$this->observaciones,
        'usr'=>$this->usr,
        'usuario'=>$this->user ? $this->user->name : null,
        'estado_id' => $this->estado_id,




    ];
    }



}
