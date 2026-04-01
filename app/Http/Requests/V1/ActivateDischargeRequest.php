<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class ActivateDischargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha_egreso' => 'required|date',
            'personal_id' => 'nullable|exists:service_personals,id',
            'tipo_documento_id' => 'required|exists:document_types,id',
            'receta_id' => ['sometimes', 'nullable', 'integer', 'required_if:tipo_documento_id,10'],
            'servicio_id' => 'nullable|integer',
            'proveedor_id' => 'nullable|integer',
            'observaciones' => 'nullable|string|max:500',
            'estado_id' => 'required|integer|in:28',
            'discharge_details' => 'required|array|min:1',
            'discharge_details.*.ingreso_detalle_id' => 'required|integer|exists:entry_details,id',
            'discharge_details.*.receta_item_id' => ['nullable', 'integer'],
            'discharge_details.*.cantidad_solicitada' => 'required|integer|min:1',
            'discharge_details.*.costo_unitario' => 'required|numeric|min:0',
            'discharge_details.*.costo_total' => 'required|numeric|min:0',
            'discharge_details.*.observaciones' => 'nullable|string',
        ];
    }

    protected function prepareForValidation(): void
    {
        $details = array_map(function (array $detail) {
            if (array_key_exists('receta_item_id', $detail) && (int) $detail['receta_item_id'] === 0) {
                $detail['receta_item_id'] = null;
            }

            return $detail;
        }, $this->input('discharge_details', []));

        $recetaId = $this->input('receta_id');

        $this->merge([
            'receta_id' => (is_numeric($recetaId) && (int) $recetaId === 0) ? null : $recetaId,
            'discharge_details' => $details,
        ]);
    }

    public function messages(): array
    {
        return [
            'estado_id.in' => 'La activacion debe enviar el estado ACTIVO (28).',
            'discharge_details.required' => 'Debes ingresar al menos un item del egreso.',
            'discharge_details.*.ingreso_detalle_id.exists' => 'El ingreso asociado no existe.',
            'discharge_details.*.receta_item_id.required_if' => 'El item de receta es obligatorio en egresos por receta.',
        ];
    }
}