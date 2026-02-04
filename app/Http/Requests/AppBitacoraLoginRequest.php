<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppBitacoraLoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'user' => ['required','string','max:150'],     // email o username
            'pwd'  => ['required','string','max:255'],
            'device_name' => ['nullable','string','max:80'] // token por dispositivo
        ];
    }
}
