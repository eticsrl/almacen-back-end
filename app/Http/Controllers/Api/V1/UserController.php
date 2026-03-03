<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\V1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the users
     */
    public function index()
    {
        $users = User::with('roles')->get();

        if ($users->isNotEmpty()) {
            return response()->json([
                'status' => 200,
                'data' => UserResource::collection($users)
            ]);
        }

        return response()->json([
            'status' => 404,
            'message' => 'No se encontraron usuarios'
        ], 404);
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users|max:255',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->messages()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Usuario creado exitosamente',
            'data' => new UserResource($user->load('roles'))
        ], 201);
    }

    /**
     * Display the specified user
     */
    public function show($id)
    {
        $user = User::with('roles', 'entity')->find($id);

        if ($user) {
            return response()->json([
                'status' => 200,
                'data' => new UserResource($user)
            ], 200);
        }

        return response()->json([
            'status' => 404,
            'message' => 'Usuario no encontrado'
        ], 404);
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|nullable|string|min:6|confirmed',
            'avatar' => 'nullable|string|max:255',
            'entity_id' => 'nullable|integer|exists:entities,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->messages()
            ], 422);
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->has('avatar')) {
            $data['avatar'] = $request->avatar;
        }

        if ($request->has('entity_id')) {
            $data['entity_id'] = $request->entity_id;
        }

        $user->update($data);

        return response()->json([
            'status' => 200,
            'message' => 'Usuario actualizado exitosamente',
            'data' => new UserResource($user->load('roles'))
        ], 200);
    }

    /**
     * Delete the specified user
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $user->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Usuario eliminado exitosamente'
        ], 200);
    }

    /**
     * Get roles for a user
     */
    public function getRoles($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $roles = $user->roles;

        return response()->json([
            'status' => 200,
            'data' => $roles
        ], 200);
    }

    /**
     * Assign roles to a user
     */
    public function assignRoles(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'role_ids' => 'required|array',
            'role_ids.*' => 'integer|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->messages()
            ], 422);
        }

        $user->roles()->sync($request->role_ids);

        return response()->json([
            'status' => 200,
            'message' => 'Roles asignados exitosamente',
            'data' => new UserResource($user->load('roles'))
        ], 200);
    }
}
