<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\UserRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function create(UserRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'avatar' => $validated['avatar'] ?? null,
            'entity_id' => $validated['entity_id'] ?? null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Usuario creado satisfactoriamente',
            'token' => $user->createToken('API TOKEN')->plainTextToken,
            'user' => new UserResource($user->load('entity', 'roles')),
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:100',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'status' => false,
                'errors' => ['No autorizado']
            ], 401);
        }

        $user = User::where('email', $request->email)->first();

        return response()->json([
            'status' => true,
            'message' => 'Usuario logeado satisfactoriamente',
            'token' => $user->createToken('API TOKEN')->plainTextToken,
            'user' => new UserResource($user->load('entity', 'roles')),
        ]);
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'status' => true,
                'message' => 'Usuario salió satisfactoriamente',
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Ningún usuario autenticado.',
        ], 401);
    }

    public function profile(Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Información del usuario',
            'user' => new UserResource($request->user()->load('entity', 'roles')),
        ]);
    }
}
