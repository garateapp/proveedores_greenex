<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreControlAccessLogIngestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'records' => ['required', 'array', 'min:1'],
            'records.*.fecha' => ['nullable', 'string'],
            'records.*.personal_id' => ['required', 'string', 'max:255'],
            'records.*.nombre' => ['nullable', 'string', 'max:255'],
            'records.*.departamento' => ['nullable', 'string', 'max:255'],
            'records.*.primera_entrada' => ['nullable', 'string'],
            'records.*.ultima_salida' => ['nullable', 'string'],
            'records.*.pin' => ['nullable', 'string', 'max:255'],
            'records.*.fecha_operativa' => ['nullable', 'string'],
            'records.*.turno' => ['nullable', 'string', 'max:20'],
            'records.*.max_event_id_pair' => ['nullable', 'string', 'max:255'],
            'records.*.pair_max_time' => ['nullable', 'string'],
        ];
    }
}
