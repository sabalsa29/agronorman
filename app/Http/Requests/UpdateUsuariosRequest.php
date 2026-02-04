<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUsuariosRequest extends FormRequest
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
        $userId = $this->route('usuario')->id ?? null;

        return [
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $userId . '|max:255',
            'password' => 'nullable|string|min:8',
            'cliente_id' => 'nullable|exists:clientes,id',
            'role_id' => 'required|exists:roles,id',
            'grupo_id' => 'nullable|exists:grupos,id',
            'acceso_app' => 'nullable|array',
            'acceso_app.*' => 'in:pia,bitacora',
        ];
    }
}
