<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles
     */
    public function index()
    {
        $roles = Role::with('permissions')->get();

        if ($roles->isNotEmpty()) {
            return response()->json([
                'status' => 200,
                'data' => $roles
            ]);
        }

        return response()->json([
            'status' => 404,
            'message' => 'No se encontraron roles'
        ], 404);
    }

    /**
     * Store a newly created role
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->messages()
            ], 422);
        }

        $role = Role::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Rol creado exitosamente',
            'data' => $role
        ], 201);
    }

    /**
     * Display the specified role
     */
    public function show($id)
    {
        $role = Role::with('permissions')->find($id);

        if ($role) {
            return response()->json([
                'status' => 200,
                'data' => $role
            ], 200);
        }

        return response()->json([
            'status' => 404,
            'message' => 'Rol no encontrado'
        ], 404);
    }

    /**
     * Update the specified role
     */
    public function update(Request $request, $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'status' => 404,
                'message' => 'Rol no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:roles,name,' . $id,
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->messages()
            ], 422);
        }

        $role->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Rol actualizado exitosamente',
            'data' => $role
        ], 200);
    }

    /**
     * Delete the specified role
     */
    public function destroy($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'status' => 404,
                'message' => 'Rol no encontrado'
            ], 404);
        }

        $role->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Rol eliminado exitosamente'
        ], 200);
    }

    /**
     * Get permissions for a role
     */
    public function getPermissions($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'status' => 404,
                'message' => 'Rol no encontrado'
            ], 404);
        }

        $permissions = $role->permissions;

        return response()->json([
            'status' => 200,
            'data' => $permissions
        ], 200);
    }

    /**
     * Assign permissions to a role
     */
    public function assignPermissions(Request $request, $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'status' => 404,
                'message' => 'Rol no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->messages()
            ], 422);
        }

        $role->permissions()->sync($request->permission_ids);

        return response()->json([
            'status' => 200,
            'message' => 'Permisos asignados exitosamente',
            'data' => $role->load('permissions')
        ], 200);
    }
}
