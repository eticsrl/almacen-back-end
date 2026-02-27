<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonalResource extends JsonResource
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
        'descripcion' => $this->apellidos_nombres,
        //'estado' => $this->estado,
        'servicio_id' => $this->id_service,
        //'service' => $this->service? $this->service->descripcion : null,
       ];
    }
}
