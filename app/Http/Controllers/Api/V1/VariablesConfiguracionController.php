<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\V1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\VariablesConfiguracion;

class VariablesConfiguracionController extends Controller
{
    public function index()
    {
        return response()->json(['data' => VariablesConfiguracion::all()]);
    }

    public function store(Request $request)
    {
        $v = VariablesConfiguracion::create($request->all());
        return response()->json(['data' => $v], 201);
    }

    public function show($id)
    {
        return response()->json(['data' => VariablesConfiguracion::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $v = VariablesConfiguracion::findOrFail($id);
        $v->update($request->all());
        return response()->json(['data' => $v]);
    }

    public function destroy($id)
    {
        VariablesConfiguracion::destroy($id);
        return response()->json(null, 204);
    }
}