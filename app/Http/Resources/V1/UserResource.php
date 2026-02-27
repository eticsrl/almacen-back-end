<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'entity' => $this->whenLoaded('entity', function () {
                return [
                    'id' => $this->entity->id ?? null,
                    'descripcion' => $this->entity->descripcion ?? null,
                ];
            }),
            'created_at' => $this->created_at,
        ];
    }
}
