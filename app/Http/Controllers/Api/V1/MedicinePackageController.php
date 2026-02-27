<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreMedicinePackageRequest;
use App\Http\Requests\V1\UpdateMedicinePackageRequest;
use App\Http\Resources\V1\MedicinePackageResource;
use App\Models\MedicinePackage;
use Illuminate\Http\Request;

class MedicinePackageController extends Controller
{
    public function index()
    {
        return MedicinePackageResource::collection(
            MedicinePackage::with(['documento', 'medicamento','user', 'estado'])->paginate(10)
        );
    }

    public function store(StoreMedicinePackageRequest $request)
    {
        $medicinePackage = MedicinePackage::create($request->validated());
        return response()->json([
            'status' => true,
            'message' => 'Registro creado exitosamente',
            'data' => new MedicinePackageResource($medicinePackage)
        ], 201);
    }

    public function show($id)
    {
        $medicinePackage = MedicinePackage::with(['documento', 'medicamento','user', 'estado'])->find($id);
        if (!$medicinePackage) {
            return response()->json(['message' => 'Registro no encontrado'], 404);
        }

        return new MedicinePackageResource($medicinePackage);
    }

    public function update(UpdateMedicinePackageRequest $request, $id)
    {
        $medicinePackage = MedicinePackage::find($id);
        if (!$medicinePackage) {
            return response()->json(['message' => 'Registro no encontrado'], 404);
        }

        $medicinePackage->update($request->validated());
        return response()->json([
            'status' => true,
            'message' => 'Registro actualizado correctamente',
            'data' => new MedicinePackageResource($medicinePackage)
        ]);
    }

    public function destroy($id)
    {
        $medicinePackage = MedicinePackage::find($id);
        if ($medicinePackage) {
            $medicinePackage->delete();
            return response()->json([
                'status'=>true,
                'message' => 'Registro eliminado exitosamente'], 200);
        }else{

            return response()->json([
                'status'=>404,
                'message'=>'Registro no encontrado'
                ],404);

        }


    }
}
