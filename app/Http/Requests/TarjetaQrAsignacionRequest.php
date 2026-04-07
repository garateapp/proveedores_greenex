<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TarjetaQrAsignacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'trabajador_id' => ['required', 'exists:trabajadores,id'],
            'asignada_en' => ['required', 'date'],
            'observaciones' => ['nullable', 'string'],
        ];
    }
}
