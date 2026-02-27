<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentTypeRequest extends FormRequest
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
            'cod_servicio' => 'required|integer',
            'usr' => 'required|integer',
            'estado_id' => 'required|integer',
        ];
    }
}
