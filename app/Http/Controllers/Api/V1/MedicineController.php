<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreMedicineRequest;
use App\Http\Requests\V1\UpdateMedicineRequest;
use App\Http\Resources\V1\MedicineResource;
use App\Models\Medicine;
use Illuminate\Http\Request;

class MedicineController extends Controller
{
    public function index()
    {
        $medicines = Medicine::with(['categoria', 'pharmaceuticalForm'])->get();
        return MedicineResource::collection($medicines);
    }

    public function store(StoreMedicineRequest $request)
    {
        $medicine = Medicine::create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Medicamento creado exitosamente',
            'data' => new MedicineResource($medicine)
        ], 201);
    }

    public function show( $id)
    {
        $medicine = Medicine::with(['categoria', 'pharmaceuticalForm'])->find($id);

        if (!$medicine) {
            return response()->json([
                'status' => false,
                'message' => 'Registro no encontrado'
            ], 404);
        }

        return new MedicineResource($medicine);
    }

    public function update(UpdateMedicineRequest $request, Medicine $medicine)
    {
        $medicine->update($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Medicamento actualizado exitosamente',
            'data' => new MedicineResource($medicine->fresh(['categoria', 'pharmaceuticalForm']))
        ], 200);
    }

    public function destroy($id)
    {


        $medicine=Medicine::find($id);

        if ($medicine){
            $medicine->delete();
            return response()->json([
                'status' => true,
                'message' => 'Tipo de documento eliminado exitosamente'
            ], 200);

        }else{

            return response()->json([
                'status'=>404,
                'message'=>'Registro no encontrado'
                ],404);

        }

    }
}

