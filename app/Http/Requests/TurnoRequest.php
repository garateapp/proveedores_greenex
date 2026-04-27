<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TurnoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $turno = $this->route('turno');

        return [
            'fecha' => ['required', 'date'],
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('turnos', 'nombre')
                    ->where('fecha', $this->input('fecha'))
                    ->ignore($turno?->id),
            ],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'activo' => ['required', 'boolean'],
            'ubicacion_ids' => ['required', 'array', 'min:1'],
            'ubicacion_ids.*' => ['integer', 'exists:ubicaciones,id'],
        ];
    }
}
