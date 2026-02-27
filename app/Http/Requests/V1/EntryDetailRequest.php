<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class EntryDetailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ingreso_id' => 'required|exists:entries,id',
            'medicamento_id' => 'required|exists:medicines,id',
            'lote' => 'required|string|max:255',
            'fecha_vencimiento' => 'required|date',
            'cantidad' => 'required|integer|min:1',
            'costo_unitario' => 'required|numeric|min:0',
            'costo_total' => 'required|numeric|min:0',
            'stock_actual' => 'required|integer|min:0',
            'observaciones' => 'nullable|string|max:255',
            'estado_id' => 'required|exists:document_types,id',
            'usuario' => 'required|exists:users,id',
            'item_id' => 'nullable|integer',
        ];
    }
}
