<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntryResource extends JsonResource
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
            'entity_id' => $this->entity_id,
            'entidad' => $this->entity->descripcion ?? null,
            'tipo_documento_id' => $this->tipo_documento_id,
            'tipo_documento' => $this->documentType->descripcion ?? null,
            'numero' => $this->numero,
            'fecha_ingreso' => $this->fecha_ingreso,
            'proveedor_id' => $this->proveedor_id,
            'proveedor' => $this->supplier->nombre ?? null,
            'num_factura' => $this->num_factura,
            'observaciones' => $this->observaciones,
            'usr' => $this->user->id ?? null,
            'usuario' => $this->user->name ?? null,
            'estado_id' => $this->estado_id,
            'estado' => $this->estate->descripcion ?? null,
            'usr_mod' => $this->usr_mod,
            'fhr_mod' => $this->fhr_mod,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'details' => EntryDetailResource::collection($this->entryDetails),

        ];
    }
}
