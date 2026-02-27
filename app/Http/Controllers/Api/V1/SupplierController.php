<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Http\Requests\V1\StoreSupplierRequest;
use App\Http\Requests\V1\UpdateSupplierRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\V1\SupplierResource;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
       // return  SupplierResource::collection(Supplier::latest()->paginate());

        $suppliers=Supplier::all();
        if($suppliers->count()>0){

         return response()->json([
             'status' => 200,
             'suppliers'=>$suppliers
         ],200);

        }
        else{
         return response()->json([
             'status' => 404,
             'suppliers'=>'No Records Found'
         ],404);
        }
     }


    public function store(StoreSupplierRequest $request)
    {
        $Supplier = Supplier::create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Registro creado exitosamente',
            'data' => new SupplierResource($Supplier)
        ], 201);


    }
    /**
     * Display the specified resource.
     */
    public function show( Supplier $supplier)
    {
        return new SupplierResource($supplier);

    }


    public function edit($id)
    {

        $supplier=Supplier::find($id);
        if ($supplier){
            return response()->json([
                'status' => 200,
                'supplier'=>$supplier
            ],200);
        }else
        {
          return response()->json([
            'status'=>404,
            'message'=>"Registro no encotrado",
        ],404);

        }

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSupplierRequest $request, Supplier $Supplier)
    {

            $Supplier->update($request->validated());
            $Supplier->refresh();
            return response()->json([
                'status' => true,
                'message' => 'Registro actualizado exitosamente',
                'data' => new SupplierResource($Supplier)
            ], 200);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
       // paso 1 eliminar la categoria
       $supplier=Supplier::find($id);

       if($supplier){
            $supplier->delete();

           return response()->json([
               'status'=>200,
               'message'=>'Registro eliminado exitosamente'
               ],200);

       }else{

           return response()->json([
               'status'=>404,
               'message'=>'Registro no encontrado'
               ],404);

        }
    }





}
