<?php
namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:8',
            'avatar' => 'nullable|string',
            'entity_id' => 'nullable|exists:entities,id',
        ];
    }
}
