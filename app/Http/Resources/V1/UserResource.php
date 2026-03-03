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
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'description' => $role->description,
                    ];
                });
            }),
            'permissions' => $this->whenLoaded('roles', function () {
                return $this->roles->flatMap(function ($role) {
                    return $role->permissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'description' => $permission->description,
                        ];
                    });
                })->unique('id')->values();
            }),
            'created_at' => $this->created_at,
        ];
    }
}
