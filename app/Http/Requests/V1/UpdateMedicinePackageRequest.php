<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicinePackageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'documento_id' => 'required|exists:document_types,id',
            'medicamento_id' => 'required|exists:medicines,id',
            'cantidad' => 'nullable|integer|min:1',
            'observaciones' => 'nullable|string',
            'dias' => 'nullable|integer|min:1',
            'estado_id' => 'required|exists:document_types,id',
            'usr' => 'required|exists:users,id',
        ];
    }
}
