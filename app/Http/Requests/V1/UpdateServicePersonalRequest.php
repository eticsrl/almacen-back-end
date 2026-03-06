<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServicePersonalRequest extends FormRequest
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
        $servicePersonalId = $this->route('servicePersonal')?->id;

        return [
            'apellidos_nombres' => 'sometimes|string|max:255|unique:service_personals,apellidos_nombres,' . $servicePersonalId,
            'estado' => 'sometimes|boolean',
            'id_service' => 'nullable|exists:document_types,id',
        ];
    }

    public function messages(): array
    {
        return [
            'apellidos_nombres.unique' => 'Este personal de servicio ya existe.',
            'apellidos_nombres.max' => 'El nombre no puede exceder 255 caracteres.',
            'id_service.exists' => 'El tipo de servicio seleccionado no existe.',
        ];
    }
}
