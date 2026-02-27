<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Entity;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\EntityRequest;
use App\Http\Resources\V1\EntityResource;

class EntityController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $entities = Entity::when($search, function ($query, $search) {
                $query->where('descripcion', 'like', "%$search%");
            })
            ->orderBy('id', 'desc')
            ->paginate(10);

        return EntityResource::collection($entities);
    }

    public function store(EntityRequest $request)
    {
        $entity = Entity::create($request->validated());

        return new EntityResource($entity);
    }

    public function show(Entity $entity)
    {
        return new EntityResource($entity);
    }

    public function update(EntityRequest $request, Entity $entity)
    {
        $entity->update($request->validated());

        return new EntityResource($entity);
    }

    public function destroy(Entity $entity)
    {
        $entity->delete();

        return response()->json(['message' => 'Registro eliminado correctamente.']);
    }
}
