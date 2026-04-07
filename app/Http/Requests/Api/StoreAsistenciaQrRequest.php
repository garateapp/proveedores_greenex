<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreAsistenciaQrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo_qr' => ['required', 'string', 'max:255'],
            'marcado_en' => ['nullable', 'date'],
            'device_id' => ['nullable', 'string', 'max:255'],
            'sync_batch_id' => ['nullable', 'string', 'max:255'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
            'ubicacion_id' => ['nullable', 'exists:ubicaciones,id'],
            'ubicacion_texto' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
