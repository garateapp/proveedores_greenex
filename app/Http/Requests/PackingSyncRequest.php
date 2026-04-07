<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PackingSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'sync_batch_id' => ['nullable', 'string', 'max:255'],
            'marcaciones' => ['required', 'array', 'min:1'],
            'marcaciones.*.uuid' => ['required', 'string', 'max:100'],
            'marcaciones.*.codigo_qr' => ['required', 'string', 'max:255'],
            'marcaciones.*.marcado_en' => ['required', 'date'],
            'marcaciones.*.device_id' => ['nullable', 'string', 'max:255'],
            'marcaciones.*.latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'marcaciones.*.longitud' => ['nullable', 'numeric', 'between:-180,180'],
            'marcaciones.*.ubicacion_texto' => ['nullable', 'string', 'max:255'],
            'marcaciones.*.metadata' => ['nullable', 'array'],
        ];
    }
}
