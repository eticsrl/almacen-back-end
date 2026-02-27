<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PharmaceuticalFormResource;
use App\Http\Requests\V1\StorePharmaceuticalFormRequest;
use App\Http\Requests\V1\UpdatePharmaceuticalFormRequest;
use App\Models\PharmaceuticalForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PharmaceuticalFormController extends Controller
{
    public function index()
    {
        $forms = PharmaceuticalForm::all();

        if ($forms->isNotEmpty()) {
            return response()->json([
                'status' => 200,
                'data' => PharmaceuticalFormResource::collection($forms)
            ]);
        }

        return response()->json([
            'status' => 404,
            'message' => 'No se encontraron registros'
        ], 404);
    }

    /**
     * Registrar una nueva forma farmacéutica
     */
    public function store(StorePharmaceuticalFormRequest $request)
    {
        $form = PharmaceuticalForm::create($request->validated());

        return response()->json([
            'status' => 201,
            'message' => 'Forma farmacéutica creada exitosamente',
            'data' => new PharmaceuticalFormResource($form)
        ], 201);
    }

    /**
     * Mostrar una forma farmacéutica por ID
     */
    public function show($id)
    {
        $form = PharmaceuticalForm::find($id);

        if (!$form) {
            return response()->json([
                'status' => 404,
                'message' => 'Registro no encontrado'
            ], 404);
        }

        return new PharmaceuticalFormResource($form);
    }

    /**
     * Actualizar una forma farmacéutica
     */
    public function update(UpdatePharmaceuticalFormRequest $request, $id)
    {
        $form = PharmaceuticalForm::find($id);

        if (!$form) {
            return response()->json([
                'status' => 404,
                'message' => 'Registro no encontrado'
            ], 404);
        }

        $form->update($request->validated());

        return response()->json([
            'status' => 200,
            'message' => 'Forma farmacéutica actualizada exitosamente',
            'data' => new PharmaceuticalFormResource($form)
        ]);
    }

    /**
     * Eliminar una forma farmacéutica
     */
    public function destroy($id)
    {
        $form = PharmaceuticalForm::find($id);

        if (!$form) {
            return response()->json([
                'status' => 404,
                'message' => 'Registro no encontrado'
            ], 404);
        }

        $form->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Forma farmacéutica eliminada exitosamente'
        ]);
    }

}
