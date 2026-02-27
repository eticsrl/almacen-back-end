<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
                'liname' => 'required|string|max:255',
                'categoriamed_id' => 'required|exists:document_types,id',
                'formafarmaceutica_id' => 'required|exists:pharmaceutical_forms,id',
                'nombre_generico' => 'nullable|string|max:255',
                'observaciones' => 'nullable|string',
                'stockmin' => 'nullable|integer|min:0',
                'stockmax' => 'nullable|integer|min:0',
                'darmax' => 'nullable|integer|min:0',
                'darmin' => 'nullable|integer|min:0',
                'estado_id' => 'required|integer',
                'usr' => 'required|integer',
        ];
    }
}
