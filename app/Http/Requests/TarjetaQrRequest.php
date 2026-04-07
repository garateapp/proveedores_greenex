<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TarjetaQrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $tarjeta = $this->route('tarjeta');

        return [
            'numero_serie' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tarjetas_qr', 'numero_serie')->ignore($tarjeta?->id),
            ],
            'codigo_qr' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tarjetas_qr', 'codigo_qr')->ignore($tarjeta?->id),
            ],
            'estado' => ['required', 'in:disponible,asignada,bloqueada,baja'],
            'observaciones' => ['nullable', 'string'],
        ];
    }
}
