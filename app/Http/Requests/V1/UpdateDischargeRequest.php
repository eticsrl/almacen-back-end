<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDischargeRequest extends FormRequest
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
            'fecha_egreso' => 'required|date',
            'personal_id' => 'nullable|exists:service_personals,id',
            'tipo_documento_id' => 'sometimes|exists:document_types,id',
            'servicio_id' => 'nullable|integer',
            'proveedor_id' => 'nullable|integer',
            'observaciones' => 'nullable|string|max:500',
            'discharge_details' => 'sometimes|array',
            'discharge_details.*.ingreso_detalle_id' => 'sometimes|exists:entry_details,id',
            'discharge_details.*.receta_item_id' => 'sometimes|integer|exists:mysql_sissu.receta_medicamentos,id',
            'discharge_details.*.cantidad_solicitada' => 'sometimes|integer|min:1',
            'discharge_details.*.costo_unitario' => 'sometimes|numeric|min:0',
            'discharge_details.*.costo_total' => 'sometimes|numeric|min:0',
            'discharge_details.*.observaciones' => 'sometimes|nullable|string',
        ];
    }
}
