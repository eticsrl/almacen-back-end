<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
                'id' => 'required|string|max:255',
                'id_service' => 'required|exists:document_types,id',
                'apellidos_nombres' => 'nullable|string|max:255',
                'estado' => 'nullable|string',];
    }
}
