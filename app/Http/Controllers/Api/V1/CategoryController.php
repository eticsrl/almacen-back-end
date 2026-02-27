<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\V1\CategoryResource;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::all();

        if ($categories->isNotEmpty()) {
            return response()->json([
                'status' => 200,
                'categories' => CategoryResource::collection($categories)
            ]);
        }

        return response()->json([
            'status' => 404,
            'message' => 'No se encontraron registros'
        ], 404);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator=Validator::make($request->all(),[
            'descripcion' =>'required |string|max:300',

        ]);
        if($validator->fails()){
            return response()->json([
                'status'=>422,
                'errors'=>$validator->messages()
            ],422);
        } else{
            $category=Category::create([
                'descripcion' => $request['descripcion'],
            ]);

            if ($category){
                return response()->json([
                    'status'=>200,
                    'message'=>"creado exitosamente ",
                    'category' => new CategoryResource($category)
                ]);
            } else{
                return response()->json([
                    'status'=>500,
                    'message'=>"Algo salio mal ",
                ]);
            }
        }

    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //return new CategoryResource($category);
        $category=Category::find($id);
        if ($category){
            return response()->json([
                'status' => 200,
                'category'=>new CategoryResource($category)
            ],200);


        }else
        {
          return response()->json([
            'status'=>404,
            'message'=>"Registro no encotrado",
        ],404);

        }

    }
    public function edit($id)
    {
        //return new CategoryResource($category);
        $category=Category::find($id);
        if ($category){
            return response()->json([
                'status' => 200,
                'category'=>$category
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
    public function update(Request $request, int $id)
    {

        $validator=Validator::make($request->all(),[
            'descripcion' =>'required |string|max:300',

        ]);
        if($validator->fails()){
            return response()->json([
                'status'=>422,
                'errors'=>$validator->messages()
            ],422);
        } else{
            $category=Category::find($id);

            if ($category){

                $category->update([
                    'descripcion' => $request['descripcion'],
                ]);
                return response()->json([
                    'status'=>200,
                    'message'=>"Registro actualizado exitosamente ",
                    'category' => new CategoryResource($category)
                ]);
            } else{
                return response()->json([
                    'status'=>404,
                    'message'=>"Registro no encontrado",
                ],404);
            }
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {

        $category=Category::find($id);

        if($category){
            $category->delete();

            return response()->json([
                'status'=>200,
                'message'=>'Registro eliminado exitosamente',
                'id' => $id
                ],200);

        }else{

            return response()->json([
                'status'=>404,
                'message'=>'Registro no encontrado'
                ],404);

        }


    }
}
