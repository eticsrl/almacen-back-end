<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:300',
            'nit' => 'required|string|max:30',
            'direccion' => 'required|string|max:300',
            'telefono' => 'nullable|string|max:20',
            'persona_contacto' => 'required|string|max:300',
            'celular' => 'required|string|max:20',
            'email' => 'nullable|string|max:60',
            'observaciones' => 'nullable|string',
            'usr' => 'required|integer|exists:users,id',
            'estado_id' => 'required|integer',
        ];
    }
}
