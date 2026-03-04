<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ServicePersonal;
use App\Http\Requests\V1\StoreServicePersonalRequest;
use App\Http\Requests\V1\UpdateServicePersonalRequest;
use App\Http\Resources\ServicePersonalResource;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class ServicePersonalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $servicePersonals = ServicePersonal::with('documentType')
                ->paginate(10);
            
            return response()->json([
                'success' => true,
                'data' => ServicePersonalResource::collection($servicePersonals),
                'message' => 'Personal de servicio obtenido correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el personal de servicio.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreServicePersonalRequest $request): JsonResponse
    {
        try {
            $servicePersonal = ServicePersonal::create($request->validated());
            
            return response()->json([
                'success' => true,
                'data' => new ServicePersonalResource($servicePersonal->load('documentType')),
                'message' => 'Personal de servicio creado correctamente.'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el personal de servicio.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ServicePersonal $servicePersonal): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => new ServicePersonalResource($servicePersonal->load('documentType')),
                'message' => 'Personal de servicio obtenido correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el personal de servicio.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateServicePersonalRequest $request, ServicePersonal $servicePersonal): JsonResponse
    {
        try {
            $servicePersonal->update($request->validated());
            
            return response()->json([
                'success' => true,
                'data' => new ServicePersonalResource($servicePersonal->load('documentType')),
                'message' => 'Personal de servicio actualizado correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el personal de servicio.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServicePersonal $servicePersonal): JsonResponse
    {
        try {
            $servicePersonal->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Personal de servicio eliminado correctamente.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el personal de servicio.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}