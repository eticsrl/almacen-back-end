<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'categoria_id' => 'required|integer|exists:categories,id',
            'descripcion' => 'required|string|max:255',
            'usuario_id' => 'required|integer',
            'estado' => 'required|boolean',
        ];
    }
}
