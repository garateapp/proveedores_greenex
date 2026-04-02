<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CuadraturaAsistenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageWorkers() ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'mimetypes:application/pdf', 'max:15360'],
            'entidad_id' => ['required', 'integer', 'min:1'],
            'mes' => ['required', 'integer', 'between:1,12'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'archivo.required' => 'Debes seleccionar un archivo PDF.',
            'archivo.mimetypes' => 'El archivo debe estar en formato PDF.',
            'archivo.max' => 'El archivo no puede superar los 15 MB.',
            'entidad_id.required' => 'Debes seleccionar una entidad.',
            'entidad_id.integer' => 'La entidad seleccionada no es válida.',
            'mes.required' => 'Debes indicar el mes a consultar.',
            'mes.between' => 'El mes debe estar entre 1 y 12.',
        ];
    }
}
