<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class EntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entity_id' => 'required|exists:entities,id',
            'tipo_documento_id' => 'required|exists:document_types,id',
            'fecha_ingreso' => 'required|date',
            'proveedor_id' => 'required_unless:tipo_documento_id,6','nullable','exists:suppliers,id',

            'num_factura' => 'nullable|integer',
            'observaciones' => 'nullable|string',

            'estado_id' => 'sometimes|exists:document_types,id',

            'entry_details' => 'required|array|min:1',
            'entry_details.*.ingreso_id' => 'sometimes|integer', // Agregar esta línea
            
            'entry_details.*.medicamento_id' => 'required|exists:medicines,id',
            'entry_details.*.lote' => 'required|string',
            'entry_details.*.fecha_vencimiento' => 'required|date',
            'entry_details.*.cantidad' => 'required|numeric|min:1',
            'entry_details.*.costo_unitario' => 'required|numeric|min:0',
            'entry_details.*.costo_total' => 'required|numeric|min:0',
            //'entry_details.*.stock_actual' => 'required|numeric|min:0',
            'entry_details.*.observaciones' => 'nullable|string',
            'entry_details.*.estado_id' => 'sometimes|exists:document_types,id',
            'entry_details.*.item_id' => 'nullable|exists:entry_details,id',
             // Para reingresos de receta por paciente
            'entry_details.*.receta_item_id'                => ['nullable','integer'],
            'entry_details.*.origen_discharge_detail_id'    => ['nullable','exists:discharge_details,id'],
        ];
    }

    public function messages()
    {
        return [
            'entry_details.required' => 'Debes ingresar al menos un item del ingreso.',
            'entry_details.*.medicamento_id.required' => 'El medicamento es obligatorio.',
            'entry_details.*.cantidad.required' => 'La cantidad es obligatoria.',
        ];
    }
}
