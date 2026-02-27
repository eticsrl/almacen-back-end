<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServicePersonalResource extends JsonResource
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
            'apellidos_nombres' => $this->apellidos_nombres,
            'estado' => $this->estado,
            'id_service' => $this->id_service,
            'document_type' => $this->whenLoaded('documentType'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}