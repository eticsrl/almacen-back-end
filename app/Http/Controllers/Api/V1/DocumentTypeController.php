<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreDocumentTypeRequest;
use App\Http\Requests\V1\UpdateDocumentTypeRequest;
use App\Http\Resources\V1\DocumentTypeResource;
use App\Models\DocumentType;
use Illuminate\Http\Request;

class DocumentTypeController extends Controller
{
    public function index(Request $request)
    {
        $categoria_id = $request->query('categoria_id');

        $query = DocumentType::with('category:id,descripcion')
            ->select('id', 'categoria_id', 'descripcion', 'estado', 'usuario_id');

        if ($categoria_id) {
            $query->where('categoria_id', $categoria_id);
        }

        $documentTypes = $query->latest()->paginate(100);

        return DocumentTypeResource::collection($documentTypes);
    }

    public function store(StoreDocumentTypeRequest $request)
    {
        $documentType = DocumentType::create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Tipo de documento creado exitosamente',
            'data' => new DocumentTypeResource($documentType)
        ], 201);
    }

    public function show($id)
    {

       $documentType = DocumentType::find($id);

       if (!$documentType) {
           return response()->json([
               'status' => false,
               'message' => 'Registro no encontrado'
           ], 404);
       }

       return new DocumentTypeResource($documentType);

    }

    public function update(UpdateDocumentTypeRequest $request, DocumentType $documentType)
    {
        $documentType->update($request->validated());
        $documentType->refresh();
        return response()->json([
            'status' => true,
            'message' => 'Tipo de documento actualizado exitosamente',
            'data' => new DocumentTypeResource($documentType)
        ], 200);
    }

    public function destroy( $id)
    {
        $documentType=DocumentType::find($id);

        if ($documentType){
            $documentType->delete();
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
