<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
/*use App\Http\Requests\V1\StoreMedicineRequest;
use App\Http\Requests\V1\UpdateMedicineRequest;
use App\Http\Resources\V1\MedicineResource;*/
use App\Http\Requests\V1\StorePersonalRequest;
use App\Http\Requests\V1\UpdatePersonalRequest;
use App\Http\Resources\V1\PersonalResource;

use App\Models\Personal;
use Illuminate\Http\Request;

class PersonalController extends Controller
{
    public function index()
    {
        $personals = Personal::get();
        logger()->info('Listado de personal obtenido', ['count' => $personals->count()]);
        return PersonalResource::collection($personals);
    }

    public function store(StorePersonalRequest $request)
    {
        $personal = Personal::create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Personal creado exitosamente',
            'data' => new PersonalResource($personal)
        ], 201);
    }

    public function show( $id)
    {
        $personal = Personal::with(['categoria', 'pharmaceuticalForm'])->find($id);

        if (!$personal) {
            return response()->json([
                'status' => false,
                'message' => 'Registro no encontrado'
            ], 404);
        }

        return new PersonalResource($personal);
    }

    public function update(UpdatePersonalRequest $request, Personal $personal)
    {
        $personal->update($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Personal actualizado exitosamente',
            'data' => new PersonalResource($personal->fresh(['categoria', 'pharmaceuticalForm']))
        ], 200);
    }

    public function destroy($id)
    {


        $personal=Personal::find($id);

        if ($personal){
            $personal->delete();
            return response()->json([
                'status' => true,
                'message' => 'Personal eliminado exitosamente'
            ], 200);

        }else{

            return response()->json([
                'status'=>404,
                'message'=>'Registro no encontrado'
                ],404);

        }

    }
}

