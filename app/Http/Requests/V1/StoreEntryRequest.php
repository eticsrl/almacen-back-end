<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class EntryRequest extends FormRequest
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
            'entity_id' => 'required|exists:entities,id',
            'tipo_documento_id' => 'required|exists:document_types,id',
            'numero' => 'required|integer',
            'fecha_ingreso' => 'required|date',
            'proveedor_id' => 'required|exists:suppliers,id',
            'num_factura' => 'required|integer',
            'observaciones' => 'nullable|string|max:255',
            'usr' => 'required|exists:users,id',
            'estado_id' => 'required|exists:document_types,id',

            'detalles' => 'required|array|min:1',
            'detalles.*.medicamento_id' => 'required|exists:medicines,id',
            'detalles.*.lote' => 'required|string|max:255',
            'detalles.*.fecha_vencimiento' => 'required|date',
            'detalles.*.cantidad' => 'required|integer|min:1',
            'detalles.*.costo_unitario' => 'required|numeric|min:0',
            'detalles.*.costo_total' => 'required|numeric|min:0',
            'detalles.*.stock_actual' => 'required|integer|min:0',
            'detalles.*.observaciones' => 'nullable|string|max:255',
            'detalles.*.estado_id' => 'required|exists:document_types,id',
            'detalles.*.usr' => 'required|exists:users,id',
        ];
    }
    public function messages()
    {
        return [

            'details.required' => 'Debes ingresar al menos un item del ingreso.',
            'details.*.medicamento_id.required' => 'El medicamento es obligatorio.',
            'details.*.cantidad.required' => 'La cantidad es obligatoria',

        ];
    }

}
