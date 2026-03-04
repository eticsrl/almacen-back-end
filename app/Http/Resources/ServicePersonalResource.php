<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V1\DocumentTypeResource;

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
            'nombre_completo' => $this->apellidos_nombres,
            'estado' => $this->estado,
            'estado_label' => $this->estado ? 'Activo' : 'Inactivo',
            'id_service' => $this->id_service,
            'document_type' => new DocumentTypeResource($this->whenLoaded('documentType')),
            'is_active' => $this->estado,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}