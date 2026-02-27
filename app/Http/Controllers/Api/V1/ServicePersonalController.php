<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ServicePersonal;
use App\Http\Requests\ServicePersonalRequest;
use App\Http\Resources\ServicePersonalResource;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ServicePersonalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $servicePersonals = ServicePersonal::with('documentType')->paginate(10);
        return ServicePersonalResource::collection($servicePersonals);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ServicePersonalRequest $request)
    {
        $servicePersonal = ServicePersonal::create($request->validated());
        return new ServicePersonalResource($servicePersonal->load('documentType'));
    }

    /**
     * Display the specified resource.
     */
    public function show(ServicePersonal $servicePersonal)
    {
        return new ServicePersonalResource($servicePersonal->load('documentType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ServicePersonalRequest $request, ServicePersonal $servicePersonal)
    {
        $servicePersonal->update($request->validated());
        return new ServicePersonalResource($servicePersonal->load('documentType'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServicePersonal $servicePersonal)
    {
        $servicePersonal->delete();
        return response()->json(['message' => 'ServicePersonal eliminado correctamente.'], 200);
    }
}