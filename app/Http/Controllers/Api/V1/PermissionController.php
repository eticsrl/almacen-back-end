<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    /**
     * Display a listing of the permissions
     */
    public function index()
    {
        $permissions = Permission::all();

        if ($permissions->isNotEmpty()) {
            return response()->json([
                'status' => 200,
                'data' => $permissions
            ]);
        }

        return response()->json([
            'status' => 404,
            'message' => 'No se encontraron permisos'
        ], 404);
    }

    /**
     * Store a newly created permission
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:permissions|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->messages()
            ], 422);
        }

        $permission = Permission::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Permiso creado exitosamente',
            'data' => $permission
        ], 201);
    }

    /**
     * Display the specified permission
     */
    public function show($id)
    {
        $permission = Permission::find($id);

        if ($permission) {
            return response()->json([
                'status' => 200,
                'data' => $permission
            ], 200);
        }

        return response()->json([
            'status' => 404,
            'message' => 'Permiso no encontrado'
        ], 404);
    }

    /**
     * Update the specified permission
     */
    public function update(Request $request, $id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'status' => 404,
                'message' => 'Permiso no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:permissions,name,' . $id,
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->messages()
            ], 422);
        }

        $permission->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Permiso actualizado exitosamente',
            'data' => $permission
        ], 200);
    }

    /**
     * Delete the specified permission
     */
    public function destroy($id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'status' => 404,
                'message' => 'Permiso no encontrado'
            ], 404);
        }

        $permission->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Permiso eliminado exitosamente'
        ], 200);
    }
}
